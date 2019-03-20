<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application;

use Experius\Util\Console\Helper\AkeneoHelper;
use Experius\Util\OperatingSystem;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AkeneoDetector
 *
 * @package Experius\Akeneo\Application
 */
class AkeneoDetector
{
    /**
     * @param \Symfony\Component\Console\Input\InputInterface|null $input
     * @param \Symfony\Component\Console\Output\OutputInterface|null $output
     * @param \Experius\Akeneo\Application\Config $config
     * @param \Symfony\Component\Console\Helper\HelperSet $helperSet
     * @param string $akeneoRootDirectory
     * @return \Experius\Akeneo\Application\DetectionResult
     */
    public function detect(
        InputInterface $input,
        OutputInterface $output,
        Config $config,
        HelperSet $helperSet,
        $akeneoRootDirectory = null
    ) {
        $input = $input ?: new ArgvInput();
        $output = $output ?: new ConsoleOutput();

        $folder = OperatingSystem::getCwd();
        $subFolders = [];

        $directRootDirectory = $this->getDirectRootDirectory($input);

        if (is_string($directRootDirectory)) {
            $folder = $this->resolveRootDirOption($directRootDirectory);
        } elseif ($akeneoRootDirectory !== null) {
            $subFolders = [$akeneoRootDirectory];
        } else {
            $subFolders = $config->getDetectSubFolders();
        }

        $helperSet->set(new AkeneoHelper($input, $output), 'akeneo');
        /* @var $akeneoHelper AkeneoHelper */
        $akeneoHelper = $helperSet->get('akeneo');

        return new DetectionResult($akeneoHelper, $folder, $subFolders); // @TODO must be refactored
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function getDirectRootDirectory(InputInterface $input)
    {
        return $input->getParameterOption('--root-dir');
    }

    /**
     * Set root dir (chdir()) of akeneo directory
     *
     * @param string $path to Akeneo directory
     * @return string
     */
    private function resolveRootDirOption($path)
    {
        $path = trim($path);

        if (strpos($path, '~') === 0) {
            $path = OperatingSystem::getHomeDir() . substr($path, 1);
        }

        $path = realpath($path);

        if (is_dir($path)) {
            chdir($path);
        }

        return $path;
    }
}