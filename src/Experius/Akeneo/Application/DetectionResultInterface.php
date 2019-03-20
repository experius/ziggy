<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application;

/**
 * Interface DetectionResultInterface
 *
 * @package Experius\Akeneo\Application
 */
interface DetectionResultInterface
{
    /**
     * @return string
     */
    public function getRootFolder();

    /**
     * @return bool
     */
    public function isEnterpriseEdition();

    /**
     * @return int
     */
    public function getMajorVersion();

    /**
     * @return boolean
     */
    public function isZiggyStopFileFound();

    /**
     * @return string
     */
    public function getZiggyStopFileFolder();
}