<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model;

use Emico\TweakwiseExport\Cron\Export;
use Exception;
use InvalidArgumentException;
use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Cron\Model\Schedule;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Most of this class came from https://github.com/netz98/n98-magerun2/:
 * - N98/Magento/Command/System/Cron/AbstractCronCommand.php
 * - N98/Magento/Command/System/Cron/ScheduleCommand.php
 *
 */
class Scheduler
{
    /**
     * @var Collection
     */
    protected $scheduleCollection;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @param Collection $scheduleCollection
     * @param ProductMetadataInterface $productMetadata
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezone
     */
    public function __construct(Collection $scheduleCollection, ProductMetadataInterface $productMetadata,  DateTime $dateTime, TimezoneInterface $timezone)
    {
        $this->scheduleCollection = $scheduleCollection;
        $this->productMetadata = $productMetadata;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
    }

    /**
     * Schedule new export
     *
     * @throws InvalidArgumentException
     * @throws Exception
     * @return Schedule
     */
    public function schedule()
    {
        $createdAtTime = $this->getCronTimestamp();
        $scheduledAtTime = $createdAtTime;

        /* @var $schedule Schedule */
        $schedule = $this->scheduleCollection->getNewEmptyItem();
        $schedule->setJobCode(Export::JOB_CODE)
            ->setStatus(Schedule::STATUS_PENDING)
            ->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', $createdAtTime))
            ->setScheduledAt(strftime('%Y-%m-%d %H:%M', $scheduledAtTime));

        $schedule->save();

        return $schedule;
    }

    /**
     * Get timestamp used for time related database fields in the cron tables
     *
     * Note: The timestamp used will change from Magento 2.1.7 to 2.2.0 and
     *       these changes are branched by Magento version in this method.
     *
     * @return int
     */
    protected function getCronTimestamp()
    {
        /* @var $version string e.g. "2.1.7" */
        $version = $this->productMetadata->getVersion();
        if (version_compare($version, '2.2.0') >= 0) {
            return $this->dateTime->gmtTimestamp();
        }
        return $this->timezone->scopeTimeStamp();
    }
}
