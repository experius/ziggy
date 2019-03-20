<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

namespace Experius\Akeneo\Application;

use Experius\Util\Console\Helper\AkeneoHelper;

/**
 * Class DetectionResult
 *
 * @package Experius\Akeneo\Application
 */
class DetectionResult implements DetectionResultInterface
{
    /**
     * @var bool
     */
    private $detected;

    /**
     * @var AkeneoHelper
     */
    private $helper;

    /**
     * DetectionResult constructor.
     *
     * @param AkeneoHelper $helper
     * @param string $folder
     * @param array $subFolders
     */
    public function __construct(AkeneoHelper $helper, $folder, array $subFolders =[])
    {
        $this->helper = $helper;
        $this->detected = $helper->detect($folder, $subFolders); // @TODO Constructor should not run "detect" method
    }

    /**
     * @return bool
     */
    public function isDetected()
    {
        return $this->detected;
    }

    /**
     * @return string
     */
    public function getRootFolder()
    {
        return $this->helper->getRootFolder();
    }

    /**
     * @return bool
     */
    public function isEnterpriseEdition()
    {
        return $this->helper->isEnterpriseEdition();
    }

    /**
     * @return int
     */
    public function getMajorVersion()
    {
        return $this->helper->getMajorVersion();
    }

    /**
     * @return boolean
     */
    public function isZiggyStopFileFound()
    {
        return $this->helper->isZiggyStopFileFound();
    }

    /**
     * @return string
     */
    public function getZiggyStopFileFolder()
    {
        return $this->helper->getZiggyStopFileFolder();
    }
}