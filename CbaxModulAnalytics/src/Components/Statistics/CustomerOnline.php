<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

use Cbax\ModulAnalytics\Components\Base;

class CustomerOnline implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $customerRepository,
        private readonly Connection $connection
    ) {

    }

    public function getStatisticsData($parameters, Context $context): array
    {
        if (empty($parameters['salesChannelIds'])) {
            $parameters['salesChannelIds'] = $this->connection->fetchFirstColumn(
                "SELECT LOWER(HEX(id)) FROM `sales_channel` WHERE type_id = ?",
                [UUid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_STOREFRONT)]
            );
        }

        $allSessions = glob(session_save_path() . '/sess_*');
        if (empty($allSessions)) {
            $allSessions = glob(sys_get_temp_dir() . '/sess_*');
        }
        $counter10 = 0;
        $counter30 = 0;
        $counter60 = 0;
        $counter120 = 0;
        $counter240 = 0;
        $nowTime = time();
        foreach ($allSessions as $sessionFile) {
            $fileLastChangeTimeAgo = $nowTime - filectime($sessionFile);
            if ($fileLastChangeTimeAgo <= 14400) {
                $session = file_get_contents($sessionFile);
                if (!empty($session)) {
                    $session = str_replace("_sf2_attributes|", "", $session);
                    $session = str_replace("_sf2_meta|", "", $session);

                    try {
                        $oldErrorLevel = error_reporting(E_ALL & ~E_WARNING);
                        $session = unserialize($session);
                        error_reporting($oldErrorLevel);
                    } catch (\Exception) {
                        continue;
                    }

                    if (
                        !empty($session['visitor']) &&
                        !empty($session['sw-sales-channel-id']) &&
                        in_array($session['sw-sales-channel-id'], $parameters['salesChannelIds'])
                    ) {
                        $counter240++;
                        if ($fileLastChangeTimeAgo <= 7200) $counter120++;
                        if ($fileLastChangeTimeAgo <= 3600) $counter60++;
                        if ($fileLastChangeTimeAgo <= 1800) $counter30++;
                        if ($fileLastChangeTimeAgo <= 600) $counter10++;
                    }
                }
            }
        }

        date_default_timezone_set('UTC');
        $date4Hours = date('Y-m-d H:i:s', strtotime("-4 hour"));
        $date2Hours = date('Y-m-d H:i:s', strtotime("-2 hour"));
        $date1Hours = date('Y-m-d H:i:s', strtotime("-1 hour"));
        $date30Minutes = date('Y-m-d H:i:s', strtotime("-30 minute"));
        $date10Minutes = date('Y-m-d H:i:s', strtotime("-10 minute"));

        $criteria2 = new Criteria();
        $criteria2->setLimit(1);
        $criteria2->addAggregation(
            new FilterAggregation(
                '4-hours-logins-filter',
                new CountAggregation('4-hours-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date4Hours
                ])]
            )
        );
        $criteria2->addAggregation(
            new FilterAggregation(
                '2-hours-logins-filter',
                new CountAggregation('2-hours-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date2Hours
                ])]
            )
        );
        $criteria2->addAggregation(
            new FilterAggregation(
                '1-hours-logins-filter',
                new CountAggregation('1-hours-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date1Hours
                ])]
            )
        );
        $criteria2->addAggregation(
            new FilterAggregation(
                '30-minutes-logins-filter',
                new CountAggregation('30-minutes-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date30Minutes
                ])]
            )
        );
        $criteria2->addAggregation(
            new FilterAggregation(
                '10-minutes-logins-filter',
                new CountAggregation('10-minutes-logins', 'id'),
                [new RangeFilter('lastLogin', [
                    RangeFilter::GTE => $date10Minutes
                ])]
            )
        );

        if (!empty($parameters['salesChannelIds'])) {
            $criteria2->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        $result2 = $this->customerRepository->search($criteria2, $context);

        $data = [];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.4h',
            'visitors' => $counter240,
            'logins' => $result2->getAggregations()->get('4-hours-logins')->getCount()
        ];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.2h',
            'visitors' => $counter120,
            'logins' => $result2->getAggregations()->get('2-hours-logins')->getCount()
        ];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.1h',
            'visitors' => $counter60,
            'logins' => $result2->getAggregations()->get('1-hours-logins')->getCount()
        ];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.30min',
            'visitors' => $counter30,
            'logins' => $result2->getAggregations()->get('30-minutes-logins')->getCount()
        ];
        $data[] = [
            'time' => 'cbax-analytics.view.customerOnline.10min',
            'visitors' => $counter10,
            'logins' => $result2->getAggregations()->get('10-minutes-logins')->getCount()
        ];

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ["success" => true, "gridData" => $data];
    }
}
