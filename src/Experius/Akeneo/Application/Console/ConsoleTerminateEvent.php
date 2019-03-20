<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application\Console;

use Symfony\Component\Console\Event\ConsoleTerminateEvent as BaseConsoleTerminateEvent;

/**
 * Class ConsoleTerminateEvent
 *
 * @package Experius\Akeneo\Application\Console
 */
class ConsoleTerminateEvent extends BaseConsoleTerminateEvent
{
    use SymfonyCompatibilityTrait;
}