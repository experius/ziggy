<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application;

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Experius\Akeneo\Framework\App\Ziggy;

/**
 * Class AkeneoInitializer
 *
 * @package Experius\Akeneo\Application
 */
class AkeneoInitializer
{
    /**
     * @var \Composer\Autoload\ClassLoader
     */
    private $autoloader;

    /**
     * Magento2Initializer constructor.
     * @param \Composer\Autoload\ClassLoader $autoloader
     */
    public function __construct(ClassLoader $autoloader)
    {
        $this->autoloader = $autoloader;
    }

    /**
     * @param string $akeneoRootFolder
     * @return \AppKernel
     */
    public function init($akeneoRootFolder)
    {
        $this->requireOnce($akeneoRootFolder . '/app/autoload.php');
        $this->requireOnce($akeneoRootFolder . '/app/AppKernel.php');

        $kernel = new \AppKernel('dev', true);
        $kernel->boot();

        return $kernel;
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
}