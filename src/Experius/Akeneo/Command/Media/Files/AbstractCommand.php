<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Command\Media\Files;

use Experius\Akeneo\Command\AbstractAkeneoCommand;

/**
 * Class AbstractCommand
 *
 * @package Experius\Akeneo\Command\Media\Files
 */
class AbstractCommand extends AbstractAkeneoCommand
{

    /**
     * @var int
     */
    protected $currentStep = 1;

    /**
     * @var int
     */
    protected $totalSteps = 1;


    /**
     * Set total steps
     *
     * @param int $steps
     * @return int
     */
    protected function setTotalSteps($steps)
    {
        return $this->totalSteps = $steps;
    }

    /**
     * Get total steps
     *
     * @return int
     */
    protected function getTotalSteps()
    {
        return $this->totalSteps;
    }

    /**
     * Get current step
     *
     * @return int
     */
    protected function getCurrentStep()
    {
        return $this->currentStep;
    }

    /**
     * Advance to next step
     *
     * @return $this
     */
    protected function advanceNextStep()
    {
        $this->currentStep++;

        return $this;
    }


    /**
     * Get media base
     *
     * @return string
     */
    protected function getMediaBase()
    {
        return $this->getContainer()->getParameter('catalog_storage_dir');
    }


    /**
     * Get media files on disc
     *
     * @param string $mediaBaseDir
     * @return array
     */
    protected function getMediaFiles($mediaBaseDir)
    {
        $di = new \RecursiveDirectoryIterator($mediaBaseDir);
        return array_filter(
            iterator_to_array(new \RecursiveIteratorIterator($di)),
            function($file) {
                if (is_file($file)) {
                    return true;
                }
                return false;
            }
        );
    }

    /**
     * Get file hashes
     *
     * @param array $files
     * @param null|\Closure $callback
     * @return array
     */
    protected function getMediaFileHashes(array &$files, $callback = null)
    {
        return array_map(function($file) use ($callback) {

            $size = filesize($file);
            $md5sum = md5_file($file);

            $hash = $md5sum . ':' . $size;

            $data = [
                'file' => $file,
                'hash' => $hash,
                'md5sum' => $md5sum,
                'size' => $size
            ];

            $callback && call_user_func($callback, $data);

            return $data;
        }, $files);
    }

    /**
     * @return array
     */
    protected function getProductModelMedia()
    {
        // product query builder factory
        $pqbFactory = $this->getContainer()->get('pim_catalog.query.product_model_query_builder_factory');
        return $this->getUsedFiles($pqbFactory);
    }

    /**
     * @return array
     */
    protected function getProductMedia()
    {
        // product query builder factory
        $pqbFactory = $this->getContainer()->get('pim_catalog.query.product_query_builder_factory');
        return $this->getUsedFiles($pqbFactory);
    }

    /**
     * @param $pqbFactory
     * @return array
     */
    protected function getUsedFiles($pqbFactory)
    {
        $usedFiles = [];
        // returns a new instance of product query builder
        $pqb = $pqbFactory->create([]);
        $productsCursor = $pqb->execute();
        $mediaAttribute = $this->getContainer()->get('pim_catalog.repository.attribute')->findMediaAttributeCodes();
        foreach ($productsCursor as $product) {
            $rawValues = $product->getRawValues();
            foreach ($mediaAttribute as $attribute) {
                if (!empty($rawValues[$attribute])) {
                    foreach ($rawValues[$attribute] as $channel => $localeValue) {
                        foreach ($localeValue as $locale => $val) {
                            if (is_string($val)) {
                                $usedFiles[] = $val;
                            }
                        }
                    }
                }
            }
        }
        return $usedFiles;
    }

}
