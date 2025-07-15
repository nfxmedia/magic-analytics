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

class SalesByQuarterInvoice implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $criteria = StatisticsHelper::getBaseCriteria('invoiceDate.invoiceDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);
        $criteria->addAssociation('invoiceDate');

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-quarter1',
                new DateHistogramAggregation(
                    'order-sales-quarter-gross',
                    'invoiceDate.invoiceDateTime',
                    DateHistogramAggregation::PER_QUARTER,
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
                'filter-order-sales-quarter2',
                new DateHistogramAggregation(
                    'order-sales-quarter-net',
                    'invoiceDate.invoiceDateTime',
                    DateHistogramAggregation::PER_QUARTER,
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
                'filter-order-sales-quarter4',
                new TermsAggregation(
                    'order-ids',
                    'id'
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregationGross = $result->getAggregations()->get('order-sales-quarter-gross');
        $aggregationNet = $result->getAggregations()->get('order-sales-quarter-net');
        $orderIds = $result->getAggregations()->get('order-ids')->getKeys();

        if (empty($orderIds)) {
            return ['success' => true, 'seriesData' => []];
        }

        //Anzahl seperat ermitteln, da bei gesetztem Promo Code sonst keine Daten
        $parameters['promotionCodes'] = null;
        $quantityCriteria = StatisticsHelper::getBaseCriteriaWithOrderIds('invoiceDate.invoiceDateTime', $parameters, $orderIds);
        $quantityCriteria->addAssociation('lineItems');
        $quantityCriteria->addAssociation('invoiceDate');
        $filters[] = new NotFilter(
            NotFilter::CONNECTION_OR,
            [
                new EqualsFilter('lineItems.productId', null)
            ]
        );

        $quantityCriteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-quarter3',
                new DateHistogramAggregation(
                    'order-sales-quarter-quantity',
                    'invoiceDate.invoiceDateTime',
                    DateHistogramAggregation::PER_QUARTER,
                    null,
                    new SumAggregation('count-order', 'lineItems.quantity')
                ),
                $filters
            )
        );

        $quantityResult = $this->orderRepository->search($quantityCriteria, $context);
        $aggregationQuantity = $quantityResult->getAggregations()->get('order-sales-quarter-quantity');

        $data = [];
        foreach ($aggregationGross->getBuckets() as $bucket) {
            $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
            $data[$bucket->getKey()] = [
                'date' => str_replace(' ', ' Q', $bucket->getKey()),
                'count' => (int)$bucket->getCount(),
                'sumGross' => round($sum, 2)
            ];
        }

        foreach ($aggregationNet->getBuckets() as $bucket) {
            if (isset($data[$bucket->getKey()])) {
                $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
                $data[$bucket->getKey()]['sumNet'] = round($sum, 2);
            }
        }

        foreach ($aggregationQuantity->getBuckets() as $bucket) {
            if (isset($data[$bucket->getKey()])) {
                $data[$bucket->getKey()]['quantity'] = $bucket->getResult()?->getSum();
                $data[$bucket->getKey()]['sumNetAverage'] = round(($data[$bucket->getKey()]['sumNet'] ?? 0) / $data[$bucket->getKey()]['count'], 2);
                $data[$bucket->getKey()]['quantityAverage'] = round($data[$bucket->getKey()]['quantity'] / $data[$bucket->getKey()]['count'], 2);
            }
        }

        $data = array_reverse(array_values($data));

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $data];
    }
}
