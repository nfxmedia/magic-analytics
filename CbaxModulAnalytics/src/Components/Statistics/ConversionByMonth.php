<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class ConversionByMonth implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $visitorsRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        //visitors
        $criteria = StatisticsHelper::getBaseCriteria('date', $parameters, false);

        $criteria->addAggregation(
            new DateHistogramAggregation(
                'visitors-by-month',
                'date',
                DateHistogramAggregation::PER_MONTH,
                null,
                new SumAggregation('sum-unique-visits', 'uniqueVisits')
            )
        );

        $result = $this->visitorsRepository->search($criteria, $context);
        $aggregationVisitors = $result->getAggregations()->get('visitors-by-month');

        $data = [];

        foreach ($aggregationVisitors->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $data[$month] = [
                'date' => $month,
                'count' => 0,
                'visitors' => (int)$bucket->getResult()->getSum(),
                'conversion' => 0,
                'conversionPercent' => 0
            ];
        }

        //Orders
        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-month',
                new DateHistogramAggregation(
                    'order-sales-month',
                    'orderDate',
                    DateHistogramAggregation::PER_MONTH
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregationOrders = $result->getAggregations()->get('order-sales-month');

        foreach ($aggregationOrders->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            if (!empty($data[$month])) {
                $data[$month]['count'] = (int)$bucket->getCount();
                $data[$month]['conversion'] = $data[$month]['visitors'] > 0 ? round($data[$month]['count']/$data[$month]['visitors'], 4) : 'NA';
                $data[$month]['conversionPercent'] = $data[$month]['conversion'] == 'NA' ? 'NA' : round($data[$month]['conversion'] * 100, 2);
            }
        }

        $data = array_reverse($data);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => array_values($data)];
    }
}


