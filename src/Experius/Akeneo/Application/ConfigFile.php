<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application;

use InvalidArgumentException;
use Experius\Util\ArrayFunctions;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ConfigFileParser
 *
 * @package Experius\Akeneo\Application
 */
class ConfigFile
{
    /**
     * @var string
     */
    private $buffer;

    /**
     * @var string
     */
    private $path;

    /**
     * @param string $path
     * @return ConfigFile
     * @throws InvalidArgumentException if $path is invalid (can't be read for whatever reason)
     */
    public static function createFromFile($path)
    {
        if (!is_readable($path)) {
            throw new InvalidArgumentException(sprintf("Config-file is not readable: '%s'", $path));
        }

        $configFile = new static();
        $configFile->loadFile($path);

        return $configFile;
    }

    /**
     * @param string $path
     */
    public function loadFile($path)
    {
        $this->path = $path;
        $buffer = file_get_contents($path);
        if (!is_string($buffer)) {
            throw new InvalidArgumentException(sprintf("Invalid path for config file: '%s'", $path));
        }

        $this->setBuffer($buffer);
    }

    /**
     * @param string $buffer
     */
    public function setBuffer($buffer)
    {
        $this->buffer = $buffer;
    }

    /**
     * @param string $akeneoRootFolder
     * @param SplFileInfo|null $file [optional]
     *
     * @return void
     */
    public function applyVariables($akeneoRootFolder, SplFileInfo $file = null)
    {
        $replace = array(
            '%module%' => $file ? $file->getPath() : '',
            '%root%'   => $akeneoRootFolder,
        );

        $this->buffer = strtr($this->buffer, $replace);
    }

    /**
     * @throws RuntimeException
     */
    public function toArray()
    {
        $result = Yaml::parse($this->buffer);

        if (!is_array($result)) {
            throw new RuntimeException(sprintf("Failed to parse config-file '%s'", $this->path));
        }

        return $result;
    }

    public function mergeArray(array $array)
    {
        $result = $this->toArray();

        return ArrayFunctions::mergeArrays($array, $result);
    }
}