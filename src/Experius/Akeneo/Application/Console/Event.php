<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application\Console;

use Experius\Akeneo\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event as BaseEvent;

/**
 * Class Event
 *
 * @package Experius\Akeneo\Application\Console
 */
class Event extends BaseEvent
{
    use SymfonyCompatibilityTrait;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    public function __construct(Application $application, InputInterface $input, OutputInterface $output)
    {
        $this->application = $application;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Gets the input instance.
     *
     * @return InputInterface An InputInterface instance
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Gets the output instance.
     *
     * @return OutputInterface An OutputInterface instance
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }
}