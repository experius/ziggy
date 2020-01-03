<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Command\Media\Files;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class RemoveOrphansCommand
 *
 * @package Experius\Akeneo\Command\Media\Files
 */
class RemoveOrphansCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('media:files:removeorphans')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Dry run? (yes|no)')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Only search first L files (useful for testing)')
            ->setDescription('Remove orphaned files from disk (orphans are files which do exist but are not found the database). [Ziggy by Experius]');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Starting files check.</info>');
        // Get options
        $dryRun = $input->getOption('dry-run');
        $interactive = !$input->getOption('no-interaction');
        if (!$dryRun && $interactive) {
            $dryRun = $this->askForDryRun($input, $output);
        }
        $this->setTotalSteps($dryRun ? 2 : 3);
        $mediaBaseDir = $this->getMediaBase();
        $filesToRemove = $this->getMediaToRemove($mediaBaseDir, $input, $output);

        $this->showStats($filesToRemove['stats'], $output);
        if ($dryRun) {
            return 0;
        }
        $this->removeMediaFiles($filesToRemove['files'], $input, $output);
        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function askForDryRun(InputInterface $input, OutputInterface $output)
    {
        $question = new ConfirmationQuestion('<question>Dry run?</question> <comment>[no]</comment> : ', false);

        return $this->getHelper('question')->ask($input, $output, $question);
    }

    /**
     * Get media files to remove
     *
     * @param string $mediaBaseDir
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    protected function getMediaToRemove($mediaBaseDir, InputInterface $input, OutputInterface $output)
    {
        $quiet = $input->getOption('quiet');
        $limit = (int)$input->getOption('limit');

        $totalSteps = $this->getTotalSteps();
        $currentStep = $this->getCurrentStep();
        $this->advanceNextStep();
        !$quiet && $output->writeln("<comment>Looking up files</comment> ({$currentStep}/{$totalSteps})");

        $mediaFiles = $this->getMediaFiles($mediaBaseDir);

        $limit && ($mediaFiles = array_slice($mediaFiles, 0, $limit));

        $mediaFilesCount = count($mediaFiles);
        $progressBar = new ProgressBar($output, $mediaFilesCount);
        $progressBar->setRedrawFrequency(50);

        $mediaFilesHashes = $this->getMediaFileHashes($mediaFiles, function() use ($progressBar, $quiet) {
            !$quiet && $progressBar->advance();
        });
        !$quiet && $progressBar->finish();

        $currentStep = $this->getCurrentStep();
        $this->advanceNextStep();
        !$quiet && $output->writeln("\n<comment>Reading database data</comment> ({$currentStep}/{$totalSteps})");

        $usedMedia = array_keys(array_merge($this->getProductModelMedia(), $this->getProductMedia()));

        $mediaFilesToRemove = [];
        $sizeBefore = 0;
        $sizeAfter = 0;
        array_walk($mediaFilesHashes, function($hashInfo) use ($mediaBaseDir, &$mediaFilesToRemove, &$sizeBefore, &$sizeAfter, &$usedMedia) {
            $sizeBefore += $hashInfo['size'];
            $file = str_replace($mediaBaseDir . DIRECTORY_SEPARATOR, '', $hashInfo['file']);
            if (in_array($file, $usedMedia)) {
                // Exists in gallery or values
                $sizeAfter += $hashInfo['size'];
                return;
            }
            // Add to list of files to remove
            $mediaFilesToRemove[] = $hashInfo['file'];
        });
        $mediaFilesToRemoveCount = $mediaFilesCount - count($mediaFilesToRemove);
        return [
            'stats' => [
                'count' => [
                    'before' => $mediaFilesCount,
                    'after' => $mediaFilesToRemoveCount,
                    'percent' => (!$mediaFilesCount) ? 0 : 1 - $mediaFilesToRemoveCount / $mediaFilesCount
                ],
                'size' => [
                    'before' => $sizeBefore,
                    'after' => $sizeAfter,
                    'percent' => (!$sizeBefore) ? 0 : 1 - $sizeAfter / $sizeBefore
                ]
            ],
            'files' => $mediaFilesToRemove
        ];
    }


    /**
     * Remove orphans from disk
     *
     * @param array $filesToRemove
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function removeMediaFiles(&$filesToRemove, InputInterface $input, OutputInterface $output)
    {
        if (count($filesToRemove) < 1) {
            // Nothing to do
            return 0;
        }

        $quiet = $input->getOption('quiet');
        $totalSteps = $this->getTotalSteps();
        $currentStep = $this->getCurrentStep();
        $this->advanceNextStep();
        !$quiet && $output->writeln("<comment>Remove files from filesystem</comment> ({$currentStep}/{$totalSteps})");

        $progress = new ProgressBar($output, count($filesToRemove));

        $unlinkedCount = array_reduce($filesToRemove, function($unlinkedCount, $info) use ($progress, $quiet) {

            $unlinked = unlink($info);

            !$quiet && $progress->advance();

            return $unlinkedCount + $unlinked;
        }, 0);

        if (!$quiet) {
            $progress->finish();

            if ($unlinkedCount < 1) {
                $output->writeln("\n <error>NO FILES DELETED! do you even have write permissions?</error>\n");
            } else {
                $output->writeln("\n <info>...and it's gone... removed {$unlinkedCount} files</info>\n");
            }
        }
        return $unlinkedCount;
    }

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
        $sizeBefore = $stats['size']['before'];
        $sizeAfter = $stats['size']['after'];
        $sizePercentage = $stats['size']['percent'];
        if ($countBefore <= $countAfter) {
            $output->writeln('<info>No files to remove</info> <comment>Your media is already optimized.</comment>');
            return;
        }

        $output->writeln('<info>Statistics: (before -> after)</info>');
        $output->writeln(' <comment>files:</comment> ' .
            $countBefore . ' -> ' .
            $countAfter .
            ' (' . round($countPercentage * 100, 1) . '%)');
        $output->writeln(' <comment>size:</comment>  ' .
            $this->formatBytes($sizeBefore) . ' -> ' .
            $this->formatBytes($sizeAfter) .
            ' (' . round($sizePercentage * 100, 1) . '%)');

        $output->writeln("\n");
    }

    /**
     * @param $size
     * @param int $precision
     * @return string
     */
    protected function formatBytes($size, $precision = 2){
        $unit = ['Byte','KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];

        for($i = 0; $size >= 1024 && $i < count($unit)-1; $i++){
            $size /= 1024;
        }

        return round($size, $precision).' '.$unit[$i];
    }
}
