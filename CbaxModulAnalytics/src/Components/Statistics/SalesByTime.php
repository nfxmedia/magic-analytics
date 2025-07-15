<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByTime implements StatisticsInterface
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
                'filter-order-sales-hour',
                new DateHistogramAggregation(
                    'order-sales-hour',
                    'orderDateTime',
                    DateHistogramAggregation::PER_HOUR,
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
        $aggregation = $result->getAggregations()->get('order-sales-hour');

        $data = [];
        $seriesdata = [];

        for ($i = 0; $i <= 23; $i++) {
            $data[$i] = ['count' => 0, 'sales' => 0];
        }

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $key, new \DateTimeZone('UTC'));
            if (empty($dateTime)) {
                continue;
            }
            if (!empty($parameters['userTimeZone']) &&  $parameters['userTimeZone'] !== 'UTC') {
                $dateTime->setTimezone(new \DateTimeZone($parameters['userTimeZone']));
            }

            $hour = $dateTime->format('G');
            $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
            $data[$hour]['count'] += (int)$bucket->getCount();
            $data[$hour]['sales'] += $sum;
        }

        foreach ($data as $key => $value) {
            $seriesdata[] = [
                'name' => $key . ':00',
                'count' => $value['count'],
                'sum' => round($value['sales'], 2)
            ];
        }

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($seriesdata, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $seriesdata];
    }
}



