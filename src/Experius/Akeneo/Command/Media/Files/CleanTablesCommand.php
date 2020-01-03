<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Command\Media\Files;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class CleanTablesCommand
 *
 * @package Experius\Akeneo\Command\Media\Files
 */
class CleanTablesCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('media:files:cleantables')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Dry run? (yes|no)')
            ->setDescription('Clean media tables by deleting rows with references to non-existing images [Ziggy by Experius]');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Starting files & database check.</info>');
        // Get options
        $dryRun = $input->getOption('dry-run');
        $interactive = !$input->getOption('no-interaction');

        if (!$dryRun && $interactive) {
            //$dryRun = $this->askForDryRun($input, $output);
        }

        $this->setTotalSteps($dryRun ? 2 : 4);

        $mediaBaseDir = $this->getMediaBase();

        $filesToRemove = $this->getRecordsToRemove($mediaBaseDir, $input, $output);
        $this->showStats($filesToRemove['stats'], $output);

        if ($dryRun) {
            return 0;
        }

        $this->deleteValuesFromCatalogDb($filesToRemove['values'], $input, $output);

        return 0;
    }

//    /**
//     * Update database records to match
//     *
//     * @param array $mediaValuesToDelete
//     * @param InputInterface $input
//     * @param OutputInterface $output
//     * @return int

//$usedFiles[$val] = [
//'attribute' => $attribute,
//'channel' => $channel,
//'locale' => $locale,
//'product' => $product
//];
//     */
    protected function deleteValuesFromCatalogDb(&$mediaValuesToDelete, InputInterface $input, OutputInterface $output)
    {
        foreach ($mediaValuesToDelete as $mediaValueToDelete){
            $this->getUsedFiles();
        }

        return $this->deleteFromCatalogDb($mediaValuesToDelete, $varcharTable, $input, $output);
    }

//
//    /**
//     * Update database records to match
//     *
//     * @param array $mediaValuesToDelete
//     * @param string $table
//     * @param InputInterface $input
//     * @param OutputInterface $output
//     * @return int
//     */
//    protected function deleteFromCatalogDb(&$mediaValuesToDelete, $table, InputInterface $input, OutputInterface $output)
//    {
//        if (count($mediaValuesToDelete) < 1) {
//            // Nothing to do
//            return 0;
//        }
//
//        $quiet = $input->getOption('quiet');
//
//        /** @var \Mage_Core_Model_Resource $resource */
//        $resource = $this->getModel('core/resource', '\Mage_Core_Model_Resource');
//
//        /** @var \Magento_Db_Adapter_Pdo_Mysql $connection */
//        $connection = $resource->getConnection('core_write');
//
//        $progress = new ProgressBar($output, count($mediaValuesToDelete));
//
//        $totalSteps = $this->_getTotalSteps();
//        $currentStep = $this->_getCurrentStep();
//        $this->_advanceNextStep();
//        !$quiet && $output->writeln("<comment>Delete values from {$table}</comment> ({$currentStep}/{$totalSteps})");
//
//        $deleteCount = array_reduce($mediaValuesToDelete, function ($deleteCount, $valueIds) use ($connection, $table, $progress, $quiet) {
//
//            // Delete for one file in a single transaction
//            $connection->beginTransaction();
//            try {
//                if (count($valueIds)) {
//                    $deleteCount += $connection->delete($table, $connection->quoteInto('value_id in(?)', $valueIds));
//                }
//
//                $connection->commit();
//            } catch (\Exception $e) {
//                $connection->rollback();
//            }
//
//            !$quiet && $progress->advance();
//
//            return $deleteCount;
//        }, 0);
//
//        if (!$quiet) {
//            $progress->finish();
//
//            if ($deleteCount < 1) {
//                $output->writeln("\n <info>no records deleted</info>\n");
//            } else {
//                $output->writeln("\n <info>deleted {$deleteCount} records...</info>\n");
//            }
//        }
//
//        return $deleteCount;
//    }

    /**
     * Show stats
     *
     * @param array $stats
     * @param OutputInterface $output
     * @return void
     */
    protected function showStats(&$stats, OutputInterface $output)
    {
        $countBefore = $stats['count']['before'];
        $countAfter = $stats['count']['after'];
        $countPercentage = $stats['count']['percent'];

        if ($countBefore <= $countAfter) {
            $output->writeln('<info>No files to remove</info> <comment>YOUR MEDIA IS OPTIMIZED AS HELL!</comment>');
            return;
        }

        $output->writeln('<info>Statistics: (before -> after)</info>');
        $output->writeln(' <comment>records:</comment> ' .
            $countBefore . ' -> ' . $countAfter .
            ' (' . round($countPercentage * 100, 1) . '%)');

        $output->writeln("\n");
    }


    /**
     * Get media files to remove
     *
     * @param string $mediaBaseDir
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    protected function getRecordsToRemove($mediaBaseDir, InputInterface $input, OutputInterface $output)
    {
        $quiet = $input->getOption('quiet');

        $totalSteps = $this->getTotalSteps();
        $currentStep = $this->getCurrentStep();
        $this->advanceNextStep();
        !$quiet && $output->writeln("<comment>Looking up files</comment> ({$currentStep}/{$totalSteps})");

        $mediaFiles = array_map(
            function () {
                return true;
            },
            array_flip(
                array_map(function ($file) use ($mediaBaseDir) {
                    return ltrim(str_replace($mediaBaseDir, '', $file), '/');
                },
                    $this->getMediaFiles($mediaBaseDir))
        );

        $currentStep = $this->getCurrentStep();
        $this->advanceNextStep();
        !$quiet && $output->writeln("<comment>Reading database data</comment> ({$currentStep}/{$totalSteps})");

        $values = array_merge($this->getProductMedia(),$this->getProductModelMedia());

        var_dump($values);
        var_dump($mediaFiles);
        $valuesToRemove = array_diff_key($values, $mediaFiles);
        echo count($valuesToRemove);

        $beforeCount = array_reduce($values, function ($totalCount, $valueIds) {
            return $totalCount + count($valueIds);
        }, 0);
        $afterCount = $beforeCount - array_reduce($values, function ($totalCount, $valueIds) {
                return $totalCount + count($valueIds);
            }, 0);

        return [
            'stats' => [
                'count' => [
                    'before' => $beforeCount,
                    'after' => $afterCount,
                    'percent' => 1 - $afterCount / $beforeCount
                ]
            ],
            'values' => $valuesToRemove
        ];
    }
}