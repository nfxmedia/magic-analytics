<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cbax\ModulAnalytics\Components\Base;

class SalesByMonth implements StatisticsInterface
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

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-month1',
                new DateHistogramAggregation(
                    'order-sales-month-gross',
                    'orderDate',
                    DateHistogramAggregation::PER_MONTH,
                    null,
                    new TermsAggregation(
                        'sales-by-currency',
                        'currencyFactor',
                        null,
                        null,
                        new SumAggregation('sum-order', 'amountTotal')
                    )
                ),
                $filters
            )
        );

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-month2',
                new DateHistogramAggregation(
                    'order-sales-month-net',
                    'orderDate',
                    DateHistogramAggregation::PER_MONTH,
                    null,
                    new TermsAggregation(
                        'sales-by-currency',
                        'currencyFactor',
                        null,
                        null,
                        new SumAggregation('sum-order', 'amountNet')
                    )
                ),
                $filters
            )
        );

        //orderIds ermitteln für 2. search
        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-month4',
                new TermsAggregation(
                    'order-ids',
                    'id'
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregationGross = $result->getAggregations()->get('order-sales-month-gross');
        $aggregationNet = $result->getAggregations()->get('order-sales-month-net');
        $orderIds = $result->getAggregations()->get('order-ids')->getKeys();

        if (empty($orderIds)) {
            return ['success' => true, 'seriesData' => []];
        }

        //Anzahl seperat ermitteln, da bei gesetztem Promo Code sonst keine Daten
        $parameters['promotionCodes'] = null;
        $quantityCriteria = StatisticsHelper::getBaseCriteriaWithOrderIds('orderDateTime', $parameters, $orderIds);
        $quantityCriteria->addAssociation('lineItems');
        $filters[] = new NotFilter(
            NotFilter::CONNECTION_OR,
            [
                new EqualsFilter('lineItems.productId', null)
            ]
        );

        $quantityCriteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-month3',
                new DateHistogramAggregation(
                    'order-sales-month-quantity',
                    'orderDate',
                    DateHistogramAggregation::PER_MONTH,
                    null,
                    new SumAggregation('count-order', 'lineItems.quantity')
                ),
                $filters
            )
        );

        $quantityResult = $this->orderRepository->search($quantityCriteria, $context);
        $aggregationQuantity = $quantityResult->getAggregations()->get('order-sales-month-quantity');

        $data = [];
        $seriesdata = [];

        foreach ($aggregationGross->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $data[$month] = ['count' => 0, 'sumGross' => 0, 'sumNet' => 0, 'quantity' => 0, 'sumNetAverage' => 0, 'quantityAverage' => 0];
        }

        foreach ($aggregationGross->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
            $data[$month]['count'] += (int)$bucket->getCount();
            $data[$month]['sumGross'] += $sum;
        }

        foreach ($aggregationNet->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
            $data[$month]['sumNet'] += $sum;
        }

        foreach ($aggregationQuantity->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            if (isset($data[$month])) {
                $data[$month]['quantity'] += $bucket->getResult()?->getSum();
                $data[$month]['sumNetAverage'] += round(($data[$month]['sumNet'] ?? 0) / $data[$month]['count'], 2);
                $data[$month]['quantityAverage'] += round($data[$month]['quantity'] / $data[$month]['count'], 2);
            }
        }

        foreach ($data as $key => $value) {
            $seriesdata[] = [
                'date' => $key,
                'count' => $value['count'],
                'sumGross' => round($value['sumGross'], 2),
                'sumNet' => round($value['sumNet'], 2),
                'quantity' => $value['quantity'],
                'sumNetAverage' => $value['sumNetAverage'],
                'quantityAverage' => $value['quantityAverage']
            ];
        }

        $seriesdata = array_reverse($seriesdata);

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($seriesdata, $parameters['labels'], $parameters['config'])];
        }

        return ["success" => true, "seriesData" => $seriesdata];
    }
}

