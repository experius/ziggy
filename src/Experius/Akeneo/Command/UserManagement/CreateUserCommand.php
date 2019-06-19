<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Command\UserManagement;

use Akeneo\UserManagement\Component\Model\User;
use Akeneo\UserManagement\Component\Model\UserInterface;
use Experius\Akeneo\Command\AbstractAkeneoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

/**
 * Class CreateUserCommand
 *
 * @package Experius\Akeneo\Command\UserManagement
 */
class CreateUserCommand extends AbstractAkeneoCommand
{
    public const COMMAND_NAME = 'pim:user:create';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->addOption('username', null, InputOption::VALUE_OPTIONAL)
            ->addOption('password', null, InputOption::VALUE_OPTIONAL)
            ->addOption('firstname', null, InputOption::VALUE_OPTIONAL)
            ->addOption('lastname', null, InputOption::VALUE_OPTIONAL)
            ->addOption('email', null, InputOption::VALUE_OPTIONAL)
            ->addOption('user-default-locale-code', null, InputOption::VALUE_OPTIONAL)
            ->addOption('catalog-default-locale-code', null, InputOption::VALUE_OPTIONAL)
            ->addOption('catalog-default-scope-code', null, InputOption::VALUE_OPTIONAL)
            ->addOption('default-tree-code', null, InputOption::VALUE_OPTIONAL)
            ->setDescription('Creates a PIM user.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln("Please enter the user's information below.");

        if (($username = $input->getOption('username')) == null) {
            $username = $this->askForUsername($input, $output);
        }
        if (($password = $input->getOption('password')) == null) {
            $password = $this->askForPassword($input, $output);
            $this->confirmPassword($input, $output, $password);
        }
        if (($firstName = $input->getOption('firstname')) == null) {
            $firstName = $this->askForFirstName($input, $output);
        }
        if (($lastName = $input->getOption('lastname')) == null) {
            $lastName = $this->askForLastName($input, $output);
        }
        if (($email = $input->getOption('email')) == null) {
            $email = $this->askForEmail($input, $output);
        }
        if (($userDefaultLocaleCode = $input->getOption('user-default-locale-code')) == null) {
            $userDefaultLocaleCode = $this->askForUserDefaultLocaleCode($input, $output);
        }
        if (($catalogDefaultLocaleCode = $input->getOption('catalog-default-locale-code')) == null) {
            $catalogDefaultLocaleCode = $this->askForCatalogDefaultLocaleCode($input, $output);
        }
        if (($catalogDefaultScopeCode = $input->getOption('catalog-default-scope-code')) == null) {
            $catalogDefaultScopeCode = $this->askForCatalogDefaultScopeCode($input, $output);
        }
        if (($defaultTreeCode = $input->getOption('default-tree-code')) == null) {
            $defaultTreeCode = $this->askForDefaultTreeCode($input, $output);
        }
        $user = $this->getContainer()->get('pim_user.factory.user')->create();
        $this->getContainer()->get('pim_user.updater.user')->update(
            $user,
            [
                'username' => $username,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'user_default_locale' => $userDefaultLocaleCode,
                'catalog_default_locale' => $catalogDefaultLocaleCode,
                'catalog_default_scope' => $catalogDefaultScopeCode,
                'default_category_tree' => $defaultTreeCode,
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

        $this->addDefaultGroupTo($user);
        $this->addDefaultRoleTo($user);

        $this->getContainer()->get('pim_user.saver.user')->save($user);

        $output->writeln(sprintf("<info>User %s has been created.</info>", $username));
    }

    private function askForUsername(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('Username : ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException("The username is mandatory.");
            }

            return $answer;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForPassword(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('Password (the input will be hidden) : ');
        $question
            ->setHidden(true)
            ->setHiddenFallback(false)
            ->setValidator(function ($answer) {
                if (empty($answer)) {
                    throw new \InvalidArgumentException("The password is mandatory.");
                }

                return $answer;
            });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function confirmPassword(InputInterface $input, OutputInterface $output, string $password): void
    {
        $question = new Question('Confirm password : ');
        $question
            ->setHidden(true)
            ->setHiddenFallback(false)
            ->setValidator(function ($answer) use ($password) {
                if ($password !== $answer) {
                    throw new \InvalidArgumentException("The passwords must match.");
                }

                return $answer;
            });

        $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForFirstName(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('First name : ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException("The first name is mandatory.");
            }

            return $answer;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForLastName(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('Last name : ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException("The last name is mandatory.");
            }

            return $answer;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForEmail(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('Email : ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException("The email is mandatory.");
            }

            if (false === filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Please enter a valid email address.");
            }

            return $answer;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForUserDefaultLocaleCode(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('UI default locale code (e.g. "en_US") : ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException("The UI default locale is mandatory.");
            }

            return $answer;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForCatalogDefaultLocaleCode(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('Catalog default locale code (e.g. "en_US") : ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException("The catalog default locale is mandatory.");
            }

            return $answer;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForCatalogDefaultScopeCode(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('Catalog default scope code (e.g. "ecommerce") : ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException("The catalog default scope is mandatory.");
            }

            return $answer;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    private function askForDefaultTreeCode(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('Default tree code (e.g. "master") : ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException("The default tree is mandatory.");
            }

            return $answer;
        });

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    /**
     * Adds the default group "All" to the new user.
     */
    private function addDefaultGroupTo(UserInterface $user): void
    {
        $group = $this->getContainer()->get('pim_user.repository.group')->findOneByIdentifier(User::GROUP_DEFAULT);

        if (null === $group) {
            throw new \RuntimeException('Default user group not found.');
        }

        $user->addGroup($group);
    }

    /**
     * Adds the default role "ROLE_USER" to the new user.
     */
    private function addDefaultRoleTo(UserInterface $user): void
    {
        $role = $this->getContainer()->get('pim_user.repository.role')->findOneByIdentifier(User::ROLE_DEFAULT);

        if (null === $role) {
            throw new \RuntimeException('Default user role not found.');
        }

        $user->addRole($role);
    }

}
