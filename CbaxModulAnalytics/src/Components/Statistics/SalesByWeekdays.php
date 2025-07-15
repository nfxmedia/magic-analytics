<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByWeekdays implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross') {
            $totalColumn = 'amountTotal';
        } else {
            $totalColumn = 'amountNet';
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-day',
                new DateHistogramAggregation(
                    'order-sales-day',
                    'orderDate',
                    DateHistogramAggregation::PER_DAY,
                    null,
                    new TermsAggregation(
                        'sales-by-currency',
                        'currencyFactor',
                        null,
                        null,
                        new SumAggregation('sum-order', $totalColumn)
                    )
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('order-sales-day');

        $data = [];
        $seriesdata = [];
        $seriesdataCSV = [];
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($weekdays as $day) {
            $data[$day] = ['count' => 0, 'sales' => 0];
        }

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            $weekday = strtolower(\DateTime::createFromFormat('Y-m-d H:i:s', $key)?->format('l'));
            $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
            $data[$weekday]['count'] += (int)$bucket->getCount();
            $data[$weekday]['sales'] += $sum;
        }

        foreach ($data as $key => $value) {
            $seriesdata[] = [
                'name' => 'cbax-analytics.weekdays.' . $key,
                'count' => $value['count'],
                'sum' => round($value['sales'], 2)
            ];
            $seriesdataCSV[] = [
                'name' => $key,
                'count' => $value['count'],
                'sum' => round($value['sales'], 2)
            ];
        }

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($seriesdataCSV, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $seriesdata];
    }
}


