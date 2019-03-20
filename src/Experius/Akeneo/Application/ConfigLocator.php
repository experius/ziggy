<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application;

use InvalidArgumentException;
use Experius\Util\OperatingSystem;
use RuntimeException;

/**
 * Class ConfigLocator
 *
 * @package Experius\Akeneo\Application
 */
class ConfigLocator
{
    /**
     * @var string
     */
    private $customConfigFilename;

    /**
     * @var string
     */
    private $akeneoRootFolder;

    /**
     * ConfigLocator constructor.
     * @param string $configFilename
     * @param string $akeneoRootFolder
     */
    public function __construct($configFilename, $akeneoRootFolder)
    {
        $this->customConfigFilename = $configFilename;
        $this->akeneoRootFolder = $akeneoRootFolder;
    }

    /**
     * Obtain the user-config-file, it is placed in the homedir, e.g. ~/.ziggy.yaml
     *
     * @return ConfigFile|null
     */
    public function getUserConfigFile()
    {
        $userConfigFile = null;

        $personalConfigFilePaths = $this->getUserConfigFilePaths();

        foreach ($personalConfigFilePaths as $personalConfigFilePath) {
            try {
                $userConfigFile = ConfigFile::createFromFile($personalConfigFilePath);
                $userConfigFile->applyVariables($this->akeneoRootFolder);
                break;
            } catch (InvalidArgumentException $e) {
                $userConfigFile = null;
            }
        }

        return $userConfigFile;
    }

    /**
     * Obtain the project-config-file, it is placed in the akeneo app/etc dir, e.g. app/etc/ziggy.yaml
     *
     * @return ConfigFile|null
     */
    public function getProjectConfigFile()
    {
        if (!strlen($this->akeneoRootFolder)) {
            return null;
        }

        $projectConfigFilePath = $this->akeneoRootFolder . '/app/etc/' . $this->customConfigFilename;

        try {
            $projectConfigFile = ConfigFile::createFromFile($projectConfigFilePath);
            $projectConfigFile->applyVariables($this->akeneoRootFolder);
        } catch (InvalidArgumentException $e) {
            $projectConfigFile = null;
        }

        return $projectConfigFile;
    }

    /**
     * Obtain the (optional) stop-file-config-file, it is placed in the folder of the stop-file, always
     * prefixed with a dot: stop-file-folder/.ziggy.yaml
     *
     * @param string $ziggyStopFileFolder
     * @return ConfigFile|null
     */
    public function getStopFileConfigFile($ziggyStopFileFolder)
    {
        if (empty($ziggyStopFileFolder)) {
            return null;
        }

        $stopFileConfigFilePath = $ziggyStopFileFolder . '/.' . $this->customConfigFilename;

        if (!file_exists($stopFileConfigFilePath)) {
            return null;
        }

        try {
            $stopFileConfigFile = ConfigFile::createFromFile($stopFileConfigFilePath);
            $stopFileConfigFile->applyVariables($this->akeneoRootFolder);
        } catch (InvalidArgumentException $e) {
            $stopFileConfigFile = null;
        }

        return $stopFileConfigFile;
    }

    /**
     * @return array
     */
    private function getUserConfigFilePaths()
    {
        $paths = array();

        $homeDirectory = OperatingSystem::getHomeDir();

        if (!strlen($homeDirectory)) {
            return $paths;
        }

        if (!is_dir($homeDirectory)) {
            throw new RuntimeException(sprintf("Home-directory '%s' is not a directory.", $homeDirectory));
        }

        $basename = $this->customConfigFilename;

        if (OperatingSystem::isWindows()) {
            $paths[] = $homeDirectory . '/' . $basename;
        }
        $paths[] = $homeDirectory . '/.' . $basename;

        return $paths;
    }
}