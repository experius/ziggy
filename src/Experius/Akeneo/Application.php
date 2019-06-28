<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo;

use BadMethodCallException;
use Composer\Autoload\ClassLoader;
use Exception;
use Akeneo\Framework\ObjectManagerInterface;
use Experius\Akeneo\Application\Config;
use Experius\Akeneo\Application\ConfigurationLoader;
use Experius\Akeneo\Application\Console\ConsoleCommandEvent;
use Experius\Akeneo\Application\Console\ConsoleEvent;
use Experius\Akeneo\Application\Console\ConsoleExceptionEvent;
use Experius\Akeneo\Application\Console\ConsoleTerminateEvent;
use Experius\Akeneo\Application\Console\Events;
use Experius\Akeneo\Application\DetectionResult;
use Experius\Akeneo\Application\AkeneoInitializer;
use Experius\Akeneo\Application\AkeneoDetector;
use Experius\Akeneo\Application\VarDirectoryChecker;
use Experius\Util\Console\Helper\TwigHelper;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\EventDispatcher\EventDispatcher;
use UnexpectedValueException;

/**
 * Class Application
 *
 * @package Experius\Akeneo
 */
class Application extends BaseApplication
{
    /**
     * @var string
     */
    const APP_NAME = 'ziggy';

    /**
     * @var string
     */
    const APP_VERSION = '1.0.0-beta3';

    /**
     * @var int
     */
    const AKENEO_MAJOR_VERSION_2 = 2;
    const AKENEO_MAJOR_VERSION_3 = 3;

    /**
     * @var string
     */
    private static $logo = "
         _                   
        (_)                  
     _____  __ _  __ _ _   _ 
    |_  / |/ _` |/ _` | | | |
     / /| | (_| | (_| | |_| |
    /___|_|\__, |\__, |\__, |
            __/ | __/ | __/ |
           |___/ |___/ |___/  
 
";
    /**
     * @var ClassLoader
     */
    protected $autoloader;

    /**
     * @var \AppKernel
     */
    protected $kernel;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var bool
     */
    protected $isPharMode = false;

    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @see \Experius\Akeneo\Application::setConfigurationLoader()
     * @var ConfigurationLoader
     */
    private $configurationLoaderInjected;

    /**
     * @var string [optional] root folder not detected, but set via public setter
     * @see setAkeneoRootFolder()
     */
    private $akeneoRootFolderInjected;

    /**
     * @var int Akeneo Major Version to operate on by this Ziggy application
     */
    private $ziggyMajorVersion = self::AKENEO_MAJOR_VERSION_3;

    /**
     * @var DetectionResult of the Akeneo application (e.g. v2/v3, Enterprise/Community, root-path)
     */
    private $detectionResult;

    /**
     * @var boolean
     */
    private $autoExit = true;

    /**
     * @param ClassLoader $autoloader
     */
    public function __construct($autoloader = null)
    {
        $this->autoloader = $autoloader;
        parent::__construct(self::APP_NAME, self::APP_VERSION);
    }

    /**
     * Sets whether to automatically exit after a command execution or not.
     *
     * Implemented on this level to allow early exit on configuration exceptions
     *
     * @see run()
     *
     * @param bool $boolean Whether to automatically exit after a command execution or not
     */
    public function setAutoExit($boolean)
    {
        $this->autoExit = (bool) $boolean;
        parent::setAutoExit($boolean);
    }

    /**
     * @param bool $mode
     */
    public function setPharMode($mode)
    {
        $this->isPharMode = $mode;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return self::$logo . parent::getHelp();
    }

    public function getLongVersion()
    {
        return parent::getLongVersion() . ' by <info>Experius</info>';
    }

    /**
     * @return boolean
     */
    public function isAkeneoEnterprise()
    {
        return $this->detectionResult->isEnterpriseEdition();
    }

    /**
     * @param string $akeneoRootFolder
     */
    public function setAkeneoRootFolder($akeneoRootFolder)
    {
        $this->akeneoRootFolderInjected = $akeneoRootFolder;
    }

    /**
     * @return int|null
     */
    public function getAkeneoMajorVersion()
    {
        return $this->detectionResult ? $this->detectionResult->getMajorVersion() : null;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        // TODO(TK) getter for config / getter for config array
        return $this->config->getConfig();
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config->setConfig($config);
    }

    /**
     * Runs the current application with possible command aliases
     *
     * @param InputInterface $input An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return int 0 if everything went fine, or an error code
     * @throws \Akeneo\Framework\Exception\FileSystemException
     * @throws \Exception
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $event = new Application\Console\Event($this, $input, $output);
        $this->dispatcher->dispatch(Events::RUN_BEFORE, $event);

        /**
         * only for compatibility to old versions.
         */
        $event = new ConsoleEvent(new Command('dummy'), $input, $output);
        $this->dispatcher->dispatch('console.run.before', $event);

        $input = $this->config->checkConfigCommandAlias($input);
        if ($output instanceof ConsoleOutput) {
            $this->initAkeneo();
            $varDirectoryChecker = new VarDirectoryChecker();
            $varDirectoryChecker->check($output->getErrorOutput());
        }

        return parent::doRun($input, $output);
    }

    /**
     * Loads and initializes the Akeneo application
     *
     * @param bool $soft
     *
     * @return bool false if akeneo root folder is not set, true otherwise
     * @throws \Exception
     */
    public function initAkeneo($soft = false)
    {
        if ($this->getAkeneoRootFolder(true) === null) {
            return false;
        }

        $isAkeneo3 = $this->detectionResult->getMajorVersion() === self::AKENEO_MAJOR_VERSION_3;
        $akeneo3Initializer = new AkeneoInitializer($this->getAutoloader());
        $app = $akeneo3Initializer->init($this->getAkeneoRootFolder());

        return true;
    }

    /**
     * @return bool
     */
    public function isSingleCommand()
    {
        return false;
    }

    /**
     * @return ClassLoader
     */
    public function getAutoloader()
    {
        return $this->autoloader;
    }

    /**
     * @param ClassLoader $autoloader
     */
    public function setAutoloader(ClassLoader $autoloader)
    {
        $this->autoloader = $autoloader;
    }

    /**
     * @param InputInterface $input [optional]
     * @param OutputInterface $output [optional]
     *
     * @return int
     * @throws \Exception
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }
        $this->_addOutputStyles($output);
        if ($output instanceof ConsoleOutput) {
            $this->_addOutputStyles($output->getErrorOutput());
        }

        $this->configureIO($input, $output);

        try {
            $this->init([], $input, $output);
        } catch (Exception $e) {
            $output = new ConsoleOutput();
            $this->renderException($e, $output->getErrorOutput());
            $exitCode = max(1, min(255, (int) $e->getCode()));
            if ($this->autoExit) {
                die($exitCode);
            }

            return $exitCode;
        }

        $return = parent::run($input, $output);

        // Fix for no return values -> used in interactive shell to prevent error output
        if ($return === null) {
            return 0;
        }

        return $return;
    }

    /**
     * @param OutputInterface $output
     */
    protected function _addOutputStyles(OutputInterface $output)
    {
        $output->getFormatter()->setStyle('debug', new OutputFormatterStyle('magenta', 'white'));
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('red', 'yellow', ['bold']));
    }

    /**
     * @param array $initConfig [optional]
     * @param InputInterface $input [optional]
     * @param OutputInterface $output [optional]
     *
     * @return void
     * @throws \Exception
     */
    public function init(array $initConfig = [], InputInterface $input = null, OutputInterface $output = null)
    {
        if ($this->isInitialized) {
            return;
        }

        // Suppress DateTime warnings
        date_default_timezone_set(@date_default_timezone_get());

        // Initialize EventDispatcher early
        $this->dispatcher = new EventDispatcher();
        $this->setDispatcher($this->dispatcher);

        $input = $input ?: new ArgvInput();
        $output = $output ?: new ConsoleOutput();

        if (null !== $this->config) {
            throw new UnexpectedValueException(sprintf('Config already initialized'));
        }

        $loadExternalConfig = !$this->_checkSkipConfigOption($input);

        $this->config = new Config($initConfig, $this->isPharMode(), $output);
        if ($this->configurationLoaderInjected) {
            $this->config->setLoader($this->configurationLoaderInjected);
        }
        $this->config->loadPartialConfig($loadExternalConfig);
        $this->detectAkeneo($input, $output);

        $configLoader = $this->config->getLoader();
        $configLoader->loadStageTwo(
            $this->getAkeneoRootFolder(true),
            $loadExternalConfig,
            $this->detectionResult->getZiggyStopFileFolder()
        );
        $this->config->load();

        if ($autoloader = $this->autoloader) {
            /**
             * Include commands shipped by Akeneo 3 core
             */
            if (!$this->_checkSkipAkeneo3CoreCommandsOption($input)) {
                $this->registerAkeneoCoreCommands($input, $output);
            }
            $this->config->registerCustomAutoloaders($autoloader);
            $this->registerEventSubscribers();
            $this->config->registerCustomCommands($this);
        }

        $this->registerHelpers();

        $this->isInitialized = true;
    }

    /**
     * @param InputInterface $input
     * @return bool
     */
    protected function _checkSkipConfigOption(InputInterface $input)
    {
        return $input->hasParameterOption('--skip-config');
    }

    /**
     * @return bool
     */
    public function isPharMode()
    {
        return $this->isPharMode;
    }

    /**
     * Search for akeneo root folder
     *
     * @param InputInterface $input [optional]
     * @param OutputInterface $output [optional]
     * @return void
     * @throws \Exception
     */
    public function detectAkeneo(InputInterface $input = null, OutputInterface $output = null)
    {
        if ($this->detectionResult) {
            return;
        }

        $akeneoRootDirectory = $this->getAkeneoRootFolder(true);

        $detector = new AkeneoDetector();
        $this->detectionResult = $detector->detect(
            $input,
            $output,
            $this->config,
            $this->getHelperSet(),
            $akeneoRootDirectory
        );

        if ($this->detectionResult->isDetected()) {
            $akeneoMajorVersion = $this->detectionResult->getMajorVersion();
            if ($akeneoMajorVersion !== $this->ziggyMajorVersion) {
//                $akeneo2Initialiter = new Akeneo2Initializer($this->getHelperSet());
//                $akeneo2Initialiter->init();
            }
        }
    }

    /**
     * @return bool
     */
    protected function _checkSkipAkeneo3CoreCommandsOption(InputInterface $input)
    {
        return $input->hasParameterOption('--skip-core-commands') || getenv('MAGERUN_SKIP_CORE_COMMANDS');
    }

    /**
     * Try to bootstrap akeneo 3 and load cli application
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function registerAkeneoCoreCommands(InputInterface $input, OutputInterface $output)
    {
        $akeneoRootFolder = $this->getAkeneoRootFolder();
        if (0 === strlen($akeneoRootFolder)) {
            return;
        }

        // Akeneo was found -> register core cli commands
        try {
            $this->requireOnce($akeneoRootFolder . '/app/autoload.php');
            $this->requireOnce($akeneoRootFolder . '/app/AppKernel.php');
        } catch (\Exception $ex) {
            $this->renderException($ex, $output);
            $output->writeln(
                '<info>Use --skip-core-commands to not require the Akeneo app/bootstrap.php which caused ' .
                'the exception.</info>'
            );

            return;
        }

        $this->kernel = new \AppKernel('dev', false);
        $coreCliApplication = new \Symfony\Bundle\FrameworkBundle\Console\Application($this->kernel);
        $coreCliApplicationCommands = $coreCliApplication->all();

        foreach ($coreCliApplicationCommands as $coreCliApplicationCommand) {
            if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
                $output->writeln(
                    sprintf(
                        '<debug>Add core command </debug> <info>%s</info> -> <comment>%s</comment>',
                        $coreCliApplicationCommand->getName(),
                        get_class($coreCliApplicationCommand)
                    )
                );
            }
            $this->add($coreCliApplicationCommand);
        }
    }

    /**
     * @return \AppKernel
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * @param bool $preventException [optional] on uninitialized akeneo root folder (returns null then, caution!)
     * @return string|null
     */
    public function getAkeneoRootFolder($preventException = false)
    {
        if (null !== $this->akeneoRootFolderInjected) {
            return $this->akeneoRootFolderInjected;
        }

        if ($preventException) {
            return $this->detectionResult ? $this->detectionResult->getRootFolder() : null;
        }

        if (!$this->detectionResult) {
            throw new BadMethodCallException('Akeneo-root-folder is not yet detected (nor set)');
        }

        return $this->detectionResult->getRootFolder();
    }

    /**
     * use require-once inside a function with it's own variable scope w/o any other variables
     * and $this unbound.
     *
     * @param string $path
     */
    private function requireOnce($path)
    {
        $requireOnce = function () {
            require_once func_get_arg(0);
        };
        if (50400 <= PHP_VERSION_ID) {
            $requireOnce->bindTo(null);
        }

        $requireOnce($path);
    }

    /**
     * Override standard command registration. We want alias support.
     *
     * @param Command $command
     *
     * @return Command
     */
    public function add(Command $command)
    {
        if ($this->config) {
            $this->config->registerConfigCommandAlias($command);
        }

        return parent::add($command);
    }

    /**
     * @return void
     */
    protected function registerEventSubscribers()
    {
        $config = $this->config->getConfig();

        if (!isset($config['event']['subscriber'])) {
            return;
        }

        $subscriberClasses = $config['event']['subscriber'];
        foreach ($subscriberClasses as $subscriberClass) {
            $subscriber = new $subscriberClass();
            $this->dispatcher->addSubscriber($subscriber);
        }
    }

    /**
     * Add own helpers to helperset.
     *
     * @return void
     */
    protected function registerHelpers()
    {
        $helperSet = $this->getHelperSet();
        $config = $this->config->getConfig();

        if (empty($config)) {
            return;
        }

        // Twig
        $twigBaseDirs = [
            __DIR__ . '/../../../res/twig',
        ];
        if (isset($config['twig']['baseDirs']) && is_array($config['twig']['baseDirs'])) {
            $twigBaseDirs = array_merge(array_reverse($config['twig']['baseDirs']), $twigBaseDirs);
        }
        $helperSet->set(new TwigHelper($twigBaseDirs), 'twig');

        foreach ($config['helpers'] ?? [] as $helperName => $helperClass) {
            if (class_exists($helperClass)) {
                $helperSet->set(new $helperClass(), $helperName);
            }
        }
    }

    /**
     * @param array $initConfig [optional]
     * @param InputInterface $input [optional]
     * @param OutputInterface $output [optional]
     * @throws \Exception
     */
    public function reinit($initConfig = [], InputInterface $input = null, OutputInterface $output = null)
    {
        $this->isInitialized = false;
        $this->detectionResult = null;
        $this->config = null;
        $this->init($initConfig, $input, $output);
    }

    /**
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param ConfigurationLoader $configurationLoader
     */
    public function setConfigurationLoader(ConfigurationLoader $configurationLoader)
    {
        if ($this->config) {
            $this->config->setLoader($configurationLoader);
        } else {
            /* inject loader to be used later when config is created in */
            /* @see \Experius\Akeneo\Application::init() */
            $this->configurationLoaderInjected = $configurationLoader;
        }
    }

    /**
     * @return InputDefinition
     */
    protected function getDefaultInputDefinition()
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        /**
         * Root dir
         */
        $rootDirOption = new InputOption(
            '--root-dir',
            '',
            InputOption::VALUE_OPTIONAL,
            'Force akeneo root dir. No auto detection'
        );
        $inputDefinition->addOption($rootDirOption);

        /**
         * Skip config
         */
        $skipExternalConfig = new InputOption(
            '--skip-config',
            '',
            InputOption::VALUE_NONE,
            'Do not load any custom config.'
        );
        $inputDefinition->addOption($skipExternalConfig);

        /**
         * Skip root check
         */
        $skipExternalConfig = new InputOption(
            '--skip-root-check',
            '',
            InputOption::VALUE_NONE,
            'Do not check if ziggy runs as root'
        );
        $inputDefinition->addOption($skipExternalConfig);

        /**
         * Skip core commands
         */
        $skipAkeneo3CoreCommands = new InputOption(
            '--skip-core-commands',
            '',
            InputOption::VALUE_OPTIONAL,
            'Do not include Akeneo 3 core commands'
        );
        $inputDefinition->addOption($skipAkeneo3CoreCommands);

        return $inputDefinition;
    }

    /**
     * Runs the current command.
     *
     * If an event dispatcher has been attached to the application,
     * events are also dispatched during the life-cycle of the command.
     *
     * @return int 0 if everything went fine, or an error code
     * @throws \Exception
     * @throws \Throwable
     */
    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
        foreach ($command->getHelperSet() as $helper) {
            if ($helper instanceof InputAwareInterface) {
                $helper->setInput($input);
            }
        }

        if (null === $this->dispatcher) {
            return $command->run($input, $output);
        }

        // bind before the console.command event, so the listeners have access to input options/arguments
        try {
            $command->mergeApplicationDefinition();
            $input->bind($command->getDefinition());
        } catch (ExceptionInterface $e) {
            // ignore invalid options/arguments for now, to allow the event listeners to customize the InputDefinition
        }

        $event = new ConsoleCommandEvent($command, $input, $output);
        $e = null;

        try {
            $this->dispatcher->dispatch(ConsoleEvents::COMMAND, $event);

            if ($event->commandShouldRun()) {
                $exitCode = $command->run($input, $output);
            } else {
                $exitCode = ConsoleCommandEvent::RETURN_CODE_DISABLED;
            }
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        if (null !== $e) {
            $x = $e instanceof \Exception ? $e : new FatalThrowableError($e);
            $event = new ConsoleExceptionEvent($command, $input, $output, $x, $x->getCode());
            if (defined('\Symfony\Component\Console\ConsoleEvents::EXCEPTION')) {
                $this->dispatcher->dispatch(ConsoleEvents::EXCEPTION, $event);
            }

            if (defined('\Symfony\Component\Console\ConsoleEvents::ERROR')) {
                $this->dispatcher->dispatch(ConsoleEvents::ERROR, $event);
            }

            if ($x !== $event->getException()) {
                $e = $event->getException();
            }

            $exitCode = $event->getExitCode();
        }

        $event = new ConsoleTerminateEvent($command, $input, $output, $exitCode);
        $this->dispatcher->dispatch(ConsoleEvents::TERMINATE, $event);

        if (null !== $e) {
            throw $e;
        }

        return $event->getExitCode();
    }
}
