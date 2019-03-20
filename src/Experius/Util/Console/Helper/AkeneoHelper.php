<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */
namespace Experius\Util\Console\Helper;

use Experius\Akeneo\Application;
use Symfony\Component\Console\Helper\Helper as AbstractHelper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class AkeneoHelper
 *
 * @package Experius\Util\Console\Helper
 */
class AkeneoHelper extends AbstractHelper
{
    /**
     * @var string
     */
    protected $akeneoRootFolder = null;

    /**
     * @var int
     */
    protected $akeneoMajorVersion = \Experius\Akeneo\Application::AKENEO_MAJOR_VERSION_3;

    /**
     * @var bool
     */
    protected $akeneoEnterprise = false;

    /**
     * @var bool
     */
    protected $ziggyStopFileFound = false;

    /**
     * @var string
     */
    protected $ziggyStopFileFolder = null;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $customConfigFilename = 'ziggy.yaml';

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'akeneo';
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $input) {
            $input = new ArgvInput();
        }

        if (null === $output) {
            $output = new ConsoleOutput();
        }

        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Start Akeneo detection
     *
     * @param string $folder
     * @param array $subFolders [optional] sub-folders to check
     * @return bool
     */
    public function detect($folder, array $subFolders =[])
    {
        $folders = $this->splitPathFolders($folder);
        $folders = $this->checkZiggyFile($folders);
        $folders = array_merge($folders, $subFolders);

        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_dir($searchFolder) || !is_readable($searchFolder)) {
                continue;
            }

            $found = $this->search($searchFolder);
            if ($found) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return string
     */
    public function getRootFolder()
    {
        return $this->akeneoRootFolder;
    }

    public function getEdition()
    {
        return $this->akeneoMajorVersion;
    }

    /**
     * @return bool
     */
    public function isEnterpriseEdition()
    {
        return $this->akeneoEnterprise;
    }

    /**
     * @return int
     */
    public function getMajorVersion()
    {
        return $this->akeneoMajorVersion;
    }

    /**
     * @return boolean
     */
    public function isZiggyStopFileFound()
    {
        return $this->ziggyStopFileFound;
    }

    /**
     * @return string
     */
    public function getZiggyStopFileFolder()
    {
        return $this->ziggyStopFileFolder;
    }

    /**
     * @param string $folder
     *
     * @return array
     */
    protected function splitPathFolders($folder)
    {
        $folders =[];

        $folderParts = explode(DIRECTORY_SEPARATOR, $folder);
        foreach ($folderParts as $key => $part) {
            $explodedFolder = implode(DIRECTORY_SEPARATOR, array_slice($folderParts, 0, $key + 1));
            if ($explodedFolder !== '') {
                $folders[] = $explodedFolder;
            }
        }

        return $folders;
    }

    /**
     * Check for ziggy stop-file
     *
     * @param array $folders
     *
     * @return array
     */
    protected function checkZiggyFile(array $folders)
    {
        foreach (array_reverse($folders) as $searchFolder) {
            if (!is_readable($searchFolder)) {
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $this->output->writeln(
                        sprintf('<debug>Folder <info>%s</info> is not readable. Skip.</debug>', $searchFolder)
                    );
                }
                continue;
            }
            $stopFile = '.' . pathinfo($this->customConfigFilename, PATHINFO_FILENAME);
            $finder = Finder::create();
            $finder
                ->files()
                ->ignoreUnreadableDirs(true)
                ->depth(0)
                ->followLinks()
                ->ignoreDotFiles(false)
                ->name($stopFile)
                ->in($searchFolder);

            $count = $finder->count();
            if ($count > 0) {
                $this->ziggyStopFileFound  = true;
                $this->ziggyStopFileFolder = $searchFolder;
                $ziggyFilePath              = $searchFolder . DIRECTORY_SEPARATOR . $stopFile;
                $ziggyFileContent           = trim(file_get_contents($ziggyFilePath));
                if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
                    $message = sprintf(
                        '<debug>Found stopfile \'%s\' file with content <info>%s</info></debug>', $stopFile,
                        $ziggyFileContent
                    );
                    $this->output->writeln($message);
                }

                array_push($folders, $searchFolder . DIRECTORY_SEPARATOR . $ziggyFileContent);
            }
        }

        return $folders;
    }

    /**
     * @param string $searchFolder
     *
     * @return bool
     */
    protected function search($searchFolder)
    {
        if (OutputInterface::VERBOSITY_DEBUG <= $this->output->getVerbosity()) {
            $this->output->writeln('<debug>Search for Akeneo in folder <info>' . $searchFolder . '</info></debug>');
        }

        if (!is_dir($searchFolder . '/app')) {
            return false;
        }

        $finder = Finder::create();
        $finder
            ->ignoreUnreadableDirs(true)
            ->depth(0)
            ->followLinks()
            ->name('AppCache.php')
            ->name('AppKernel.php')
            ->name('autoload.php')
            ->in($searchFolder . '/app');

        if ($finder->count() > 0) {
            $finder = Finder::create();
            $finder
                ->ignoreUnreadableDirs(true)
                ->depth(0)
                ->followLinks()
                ->name('UPGRADE-3.0.md')
                ->in($searchFolder);
            if (!$finder->count()) {
                $this->akeneoMajorVersion = Application::AKENEO_MAJOR_VERSION_2;
            }
            $this->akeneoRootFolder = $searchFolder;
            return true;
        }

        return false;
    }
}