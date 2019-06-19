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
class ChangePasswordCommand extends AbstractAkeneoCommand
{
    public const COMMAND_NAME = 'pim:user:change-password';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->addArgument('id', InputArgument::OPTIONAL, 'Username or Email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->setDescription('Changes the password for a PIM user.');
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

        // Password
        if (($password = $input->getArgument('password')) == null) {
            $password = $this->askForPassword($input, $output);
        }

        $userManager = $this->getContainer()->get('pim_user.manager');
        /** @var User $user */
        $user = $userManager->findUserByUsernameOrEmail($id);

        if (null === $user) {
            throw new \InvalidArgumentException(sprintf('User with id %d could not be found.', $id));
        }

        $shouldUpdate = $input->getOption('force');
        if (!$shouldUpdate) {
            $symfonyStyle = new SymfonyStyle($input, $output);
            $shouldUpdate = $symfonyStyle->confirm('Are you sure?', false);
        }

        if ($shouldUpdate) {
            $this->getContainer()->get('pim_user.updater.user')->update(
                $user,
                [
                    'username' => $id,
                    'password' => $password
                ]
            );
            $errors = $this->getContainer()->get('validator')->validate($user);
            if (0 < count($errors)) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }

                throw new \InvalidArgumentException("The user creation failed :\n" . implode("\n", $errorMessages));
            }
            $this->getContainer()->get('pim_user.saver.user')->save($user);
            $output->writeln(sprintf("<info>Password has been changed for %s.</info>", $id));
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
