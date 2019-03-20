<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius;

use Composer\Autoload\ClassLoader;
use ErrorException;

/**
 * Class ZiggyBootstrap
 *
 * @package Experius
 */
class ZiggyBootstrap
{
    /**
     * @param ClassLoader|null $loader [optional]
     * @return Akeneo\Application
     * @throws ErrorException
     */
    public static function createApplication(ClassLoader $loader = null)
    {
        if (null === $loader) {
            $loader = self::getLoader();
        }

        $application = new Akeneo\Application($loader);

        return $application;
    }

    /**
     * @throws ErrorException
     * @return ClassLoader
     */
    public static function getLoader()
    {
        if (
            !($loader = self::includeIfExists(__DIR__ . '/../../vendor/autoload.php'))
            && !($loader = self::includeIfExists(__DIR__ . '/../../../../autoload.php'))
        ) {
            throw new ErrorException(
                'You must set up the project dependencies, run the following commands:' . PHP_EOL .
                'curl -s http://getcomposer.org/installer | php' . PHP_EOL .
                'php composer.phar install' . PHP_EOL
            );
        }

        return $loader;
    }

    /**
     * @param string $file
     * @return mixed
     */
    public static function includeIfExists($file)
    {
        if (file_exists($file)) {
            return include $file;
        }
    }
}