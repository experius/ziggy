<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Command\UserManagement;

use Akeneo\UserManagement\Component\Model\User;
use Experius\Akeneo\Command\AbstractAkeneoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class DeleteUserCommand
 *
 * @package Experius\Akeneo\Command\UserManagement\Command
 */
class DeleteUserCommand extends AbstractAkeneoCommand
{
    public const COMMAND_NAME = 'pim:user:delete';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->addArgument('id', InputArgument::OPTIONAL, 'Username or Email')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->setDescription('Deletes a PIM user.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectAkeneo($output);
        $output->writeln("Please enter the user's information below.");

        // Username
        if (($id = $input->getArgument('id')) == null) {
            $id = $this->askForUsername($input, $output);
        }

        $userManager = $this->getContainer()->get('pim_user.manager');
        /** @var User $user */
        $user = $userManager->findUserByUsernameOrEmail($id);

        if (null === $user) {
            throw new \InvalidArgumentException(sprintf('User with id %d could not be found.', $id));
        }

        $shouldRemove = $input->getOption('force');
        if (!$shouldRemove) {
            $symfonyStyle = new SymfonyStyle($input, $output);
            $shouldRemove = $symfonyStyle->confirm('Are you sure?', false);
        }

        if ($shouldRemove) {
            $this->getContainer()->get('pim_user.remover.user')->remove($user);
            $output->writeln(sprintf("<info>User %s has been deleted.</info>", $id));
        } else {
            $output->writeln('<error>Aborting delete</error>');
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    private function askForUsername(InputInterface $input, OutputInterface $output)
    {
        $question = new Question('Username/email : ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException(".");
            }

            return $answer;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }
}
