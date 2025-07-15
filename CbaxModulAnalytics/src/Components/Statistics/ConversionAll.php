<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class ConversionAll implements StatisticsInterface
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
                'visitors-by-day',
                'date',
                DateHistogramAggregation::PER_DAY,
                null,
                new SumAggregation('sum-unique-visits', 'uniqueVisits')
            )
        );

        $result = $this->visitorsRepository->search($criteria, $context);
        $aggregationVisitors = $result->getAggregations()->get('visitors-by-day');

        $dataVisitors = [];
        $allKeys = [];

        foreach ($aggregationVisitors->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $allKeys[] = $key;
            $dataVisitors[$key] = (int)$bucket->getResult()->getSum();
        }
        //////

        //Orders
        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-day',
                new DateHistogramAggregation(
                    'order-sales-day',
                    'orderDate',
                    DateHistogramAggregation::PER_DAY
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregationOrders = $result->getAggregations()->get('order-sales-day');

        $dataOrders = [];
        foreach ($aggregationOrders->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            if (!in_array($key, $allKeys)) $allKeys[] = $key;
            $dataOrders[$key] = (int)$bucket->getCount();
        }
        //////

        $allKeys = array_unique($allKeys);
        sort($allKeys);
        $data = [];
        foreach ($allKeys as $key) {
            $visitors = !empty($dataVisitors[$key]) ? $dataVisitors[$key] : 0;
            $orders = !empty($dataOrders[$key]) ? $dataOrders[$key] : 0;
            $conversion = $visitors > 0 ? round($orders/$visitors, 4) : 'NA';
            $data[] = [
                'date' => $key,
                'formatedDate' => StatisticsHelper::getFormatedDate($key, $parameters['adminLocalLanguage']),
                'count' => $orders,
                'visitors' => $visitors,
                'conversion' => $conversion,
                'conversionPercent' => $conversion == 'NA' ? 'NA' : round($conversion * 100, 2)
            ];
        }

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $data];
    }
}

