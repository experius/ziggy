<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Command\SubCommand;

use Experius\Akeneo\Command\AbstractAkeneoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SubCommandFactory
 *
 * @package Experius\Akeneo\Command\SubCommand
 */
class SubCommandFactory
{
    /**
     * @var string
     */
    protected $baseNamespace;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var ConfigBag
     */
    protected $config;

    /**
     * @var array
     */
    protected $commandConfig;

    /**
     * @var AbstractAkeneoCommand
     */
    protected $command;

    /**
     * @param AbstractAkeneoCommand $command
     * @param string $baseNamespace
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array $commandConfig
     * @param ConfigBag $config
     */
    public function __construct(
        AbstractAkeneoCommand $command,
        $baseNamespace,
        InputInterface $input,
        OutputInterface $output,
        array $commandConfig,
        ConfigBag $config
    ) {
        $this->baseNamespace = $baseNamespace;
        $this->command = $command;
        $this->input = $input;
        $this->output = $output;
        $this->commandConfig = $commandConfig;
        $this->config = $config;
    }

    /**
     * @param string $className
     * @param bool $userBaseNamespace
     * @return SubCommandInterface
     */
    public function create($className, $userBaseNamespace = true)
    {
        if ($userBaseNamespace) {
            $className = rtrim($this->baseNamespace, '\\') . '\\' . $className;
        }

        $subCommand = new $className();
        if (!$subCommand instanceof SubCommandInterface) {
            throw new \InvalidArgumentException('Subcommand must implement SubCommandInterface.');
        }

        // Inject objects
        $subCommand->setCommand($this->command);
        $subCommand->setInput($this->input);
        $subCommand->setOutput($this->output);
        $subCommand->setConfig($this->config);
        $subCommand->setCommandConfig($this->commandConfig);

        return $subCommand;
    }

    /**
     * @return ConfigBag
     */
    public function getConfig()
    {
        return $this->config;
    }
}
