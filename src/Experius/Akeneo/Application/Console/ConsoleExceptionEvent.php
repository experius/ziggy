<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application\Console;

/**
 * Class ConsoleExceptionEvent
 *
 * @package Experius\Akeneo\Application\Console
 */
class ConsoleExceptionEvent extends \Symfony\Component\Console\Event\ConsoleExceptionEvent
{
    use SymfonyCompatibilityTrait;
}