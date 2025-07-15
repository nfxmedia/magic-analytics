<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;
use Cbax\ModulAnalytics\Components\Base;

class SalesByQuarterPwreturn implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository,
        private readonly Connection $connection
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        if (!class_exists('Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderEntity')) {
            return ['success' => true, 'seriesData' => []];
        }

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);
        $criteria->addAssociation('invoiceDate');

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order-sales-quarter1',
                new DateHistogramAggregation(
                    'order-sales-quarter-gross',
                    'orderDate',
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
                    'orderDate',
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
                'filter-order-sales-quarter3',
                new DateHistogramAggregation(
                    'order-sales-quarter-quantity',
                    'orderDate',
                    DateHistogramAggregation::PER_QUARTER,
                    null,
                    new SumAggregation('count-order', 'lineItems.quantity')
                ),
                $filters
            )
        );

        $quantityResult = $this->orderRepository->search($quantityCriteria, $context);
        $aggregationQuantity = $quantityResult->getAggregations()->get('order-sales-quarter-quantity');

        ////////////// Pickware Returns ///////////////////////
        $returnOrderDataGrouped =  $this->getPWReturnDataByQuarter($orderIds);

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

                $data[$bucket->getKey()]['return'] = 0;
                $data[$bucket->getKey()]['refund'] = 0;
                $data[$bucket->getKey()]['newGross'] =  round($data[$bucket->getKey()]['sumGross'],2);
                $data[$bucket->getKey()]['newQuantity'] =  $data[$bucket->getKey()]['quantity'];
            }
        }

        foreach ($returnOrderDataGrouped as $quarter => $returnDataSet) {
            if (isset($data[$quarter])) {
                $data[$quarter]['return'] = $returnDataSet['return'];
                $data[$quarter]['refund'] = round($returnDataSet['refund'], 2);
                $data[$quarter]['newGross'] = round($data[$quarter]['sumGross'] - $returnDataSet['refund'],2);
                $data[$quarter]['newQuantity'] = $data[$quarter]['quantity'] - $returnDataSet['return'];
            }
        }

        $data = array_reverse(array_values($data));

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $data];
    }

    private function getPWReturnDataByQuarter(array $orderIds): array
    {
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(returnorder.id)) as returnOrderId',
                'sworder.order_date as orderDate',
                'sworder.currency_factor as currencyFactor'
            ])
            ->from('pickware_erp_return_order', 'returnorder')
            ->innerJoin('returnorder', '`order`', 'sworder', 'returnorder.order_id = sworder.id AND sworder.version_id = :versionId')
            ->andWhere('returnorder.version_id = :versionId')
            ->andWhere('returnorder.order_id IN (:orderids)')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'orderids' => Uuid::fromHexToBytesList($orderIds)
            ], [
                'orderids' => ArrayParameterType::STRING
            ]);

        $returnOrderData = $query->fetchAllAssociativeIndexed();
        $returnOrderDataGrouped =  [];

        if (!empty($returnOrderData)) {
            $qb = $this->connection->createQueryBuilder();
            $query = $qb
                ->select([
                    'LOWER(HEX(refund.return_order_id)) as returnOrderId',
                    'SUM(refund.amount) as refund'
                ])
                ->from('pickware_erp_return_order_refund', 'refund')
                ->andWhere('refund.version_id = :versionId')
                ->andWhere('refund.return_order_version_id = :versionId')
                ->andWhere('refund.return_order_id IN (:returnorderids)')
                ->setParameters([
                    'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    'returnorderids' => Uuid::fromHexToBytesList(array_keys($returnOrderData))
                ], [
                    'returnorderids' => ArrayParameterType::STRING
                ])
                ->groupBy('refund.return_order_id');

            $returnOrderRefundData = $query->fetchAllKeyValue();

            $qb = $this->connection->createQueryBuilder();
            $query = $qb
                ->select([
                    'LOWER(HEX(lineitems.return_order_id)) as returnOrderId',
                    'SUM(lineitems.quantity) as returnQuantity'
                ])
                ->from('pickware_erp_return_order_line_item', 'lineitems')
                ->andWhere('lineitems.type = :itemtype')
                ->andWhere('lineitems.version_id = :versionId')
                ->andWhere('lineitems.return_order_version_id = :versionId')
                ->andWhere('lineitems.return_order_id IN (:returnorderids)')
                ->setParameters([
                    'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    'itemtype' => 'product',
                    'returnorderids' => Uuid::fromHexToBytesList(array_keys($returnOrderData))
                ], [
                    'returnorderids' => ArrayParameterType::STRING
                ])
                ->groupBy('lineitems.return_order_id');

            $returnOrderReturnData = $query->fetchAllKeyValue();

            foreach($returnOrderData as $id => $returnData) {
                if (empty($returnData['orderDate'])) {
                    continue;
                }
                $dateTime = \DateTime::createFromFormat('Y-m-d', $returnData['orderDate']);
                if (empty($dateTime)) {
                    continue;
                }
                $quarter = $dateTime->format('Y') . ' ' . ceil($dateTime->format('n') / 3);
                if (!isset($returnOrderDataGrouped[$quarter])) {
                    $returnOrderDataGrouped[$quarter] = [
                        'refund' => (float)($returnOrderRefundData[$id] ?? 0) / ($returnData['currencyFactor'] ?? 1),
                        'return' => (int)($returnOrderReturnData[$id] ?? 0)
                    ];
                } else {
                    $returnOrderDataGrouped[$quarter]['refund'] += (float)($returnOrderRefundData[$id] ?? 0) / ($returnData['currencyFactor'] ?? 1);
                    $returnOrderDataGrouped[$quarter]['return'] += (int)($returnOrderReturnData[$id] ?? 0);
                }
            }
        }

        return $returnOrderDataGrouped;
    }
}

