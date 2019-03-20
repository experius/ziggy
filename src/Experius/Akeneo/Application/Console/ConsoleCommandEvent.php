<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application\Console;

/**
 * Class ConsoleCommandEvent
 *
 * @package Experius\Akeneo\Application\Console
 */
class ConsoleCommandEvent extends \Symfony\Component\Console\Event\ConsoleCommandEvent
{
    use SymfonyCompatibilityTrait;
}