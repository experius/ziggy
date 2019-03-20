<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Command\UserManagement;

use Akeneo\UserManagement\Component\Model\User;
use Experius\Akeneo\Command\AbstractAkeneoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Class ListCommand
 *
 * @package Experius\Akeneo\Command\UserManagement\Command
 */
class ListCommand extends AbstractAkeneoCommand
{
    public const COMMAND_NAME = 'pim:user:list';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->setDescription('List all PIM users.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectAkeneo($output);

        $userRepository = $this->getContainer()->get('pim_user.manager')->getRepository();
        $userList = $userRepository->findAll();
        $rows = [];
        foreach ($userList as $user) {
            $rows[] = [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
                $user->isEnabled() ? 'enabled' : 'disabled',
            ];
        }
        $table = new Table($output);
        $table
            ->setHeaders(array('id', 'username', 'email', 'status'))
            ->setRows($rows);
        $table->render();
    }
}
