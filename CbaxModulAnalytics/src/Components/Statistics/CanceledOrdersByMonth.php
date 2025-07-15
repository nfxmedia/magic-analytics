<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class CanceledOrdersByMonth implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $canceledId = $this->base->getCanceledStateId($context);

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);
        $criteria->addFilter(new EqualsFilter('stateId', $canceledId));

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross') {
            $totalColumn = 'amountTotal';
        } else {
            $totalColumn = 'amountNet';
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-month',
                new DateHistogramAggregation(
                    'order-sales-month',
                    'orderDate',
                    DateHistogramAggregation::PER_MONTH,
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
        $aggregation = $result->getAggregations()->get('order-sales-month');

        $data = [];
        $seriesdata = [];

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $data[$month] = ['count' => 0, 'sales' => 0];
        }

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
            $data[$month]['count'] += (int)$bucket->getCount();
            $data[$month]['sales'] += $sum;
        }

        foreach ($data as $key => $value) {
            $seriesdata[] = [
                'date' => $key,
                'count' => $value['count'],
                'sum' => round($value['sales'], 2)
            ];
        }

        $seriesdata = array_reverse($seriesdata);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($seriesdata, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $seriesdata];
    }
}
