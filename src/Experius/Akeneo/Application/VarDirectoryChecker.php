<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VarDirectoryChecker
 *
 * @package Experius\Akeneo\Application
 */
class VarDirectoryChecker
{
    /**
     * @param OutputInterface $output
     * @return null|false
     */
    public function check(OutputInterface $output)
    {
        $tempVarDir = sys_get_temp_dir() . '/akeneo/var';
        if ((!OutputInterface::VERBOSITY_NORMAL) <= $output->getVerbosity() && !is_dir($tempVarDir)) {
            return true;
        }

        $output->writeln([
            sprintf('<warning>Cache fallback folder %s was found.</warning>', $tempVarDir),
            '',
            'ziggy is using the fallback folder. If there is another folder configured for Akeneo, this ' .
            'can cause serious problems.',
            'Please refer to https://github.com/experius/ziggy/wiki/File-system-permissions ' .
            'for more information.',
            '',
        ]);

        return false;
    }
}