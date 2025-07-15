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

class SalesByMonthPwreturn implements StatisticsInterface
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

        ////////////// Pickware Returns ///////////////////////
        $returnOrderDataGrouped =  $this->getPWReturnDataByMonth($orderIds);

        $data = [];
        $seriesdata = [];

        foreach ($aggregationGross->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $data[$month] = [
                'count' => 0,
                'sumGross' => 0,
                'sumNet' => 0,
                'quantity' => 0,
                'sumNetAverage' => 0,
                'quantityAverage' => 0,
                'refund' => 0,
                'return' => 0
                ];
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
                $data[$month]['newGross'] = round($data[$month]['sumGross'],2);
                $data[$month]['newQuantity'] = $data[$month]['quantity'];
            }
        }

        foreach ($returnOrderDataGrouped as $month => $returnDataSet) {
            if (isset($data[$month])) {
                $data[$month]['return'] = $returnDataSet['return'];
                $data[$month]['refund'] = round($returnDataSet['refund'], 2);
                $data[$month]['newGross'] = round($data[$month]['sumGross'] - $returnDataSet['refund'],2);
                $data[$month]['newQuantity'] = $data[$month]['quantity'] - $returnDataSet['return'];
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
                'quantityAverage' => $value['quantityAverage'],
                'refund' => $value['refund'],
                'return' => $value['return'],
                'newGross' => $value['newGross'],
                'newQuantity' => $value['newQuantity']

            ];
        }

        $seriesdata = array_reverse($seriesdata);

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($seriesdata, $parameters['labels'], $parameters['config'])];
        }

        return ["success" => true, "seriesData" => $seriesdata];
    }

    private function getPWReturnDataByMonth(array $orderIds): array
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
                $month = \DateTime::createFromFormat('Y-m-d', $returnData['orderDate'])?->format('m/Y');
                if (!isset($returnOrderDataGrouped[$month])) {
                    $returnOrderDataGrouped[$month] = [
                        'refund' => (float)($returnOrderRefundData[$id] ?? 0) / ($returnData['currencyFactor'] ?? 1),
                        'return' => (int)($returnOrderReturnData[$id] ?? 0)
                    ];
                } else {
                    $returnOrderDataGrouped[$month]['refund'] += (float)($returnOrderRefundData[$id] ?? 0) / ($returnData['currencyFactor'] ?? 1);
                    $returnOrderDataGrouped[$month]['return'] += (int)($returnOrderReturnData[$id] ?? 0);
                }
            }
        }

        return $returnOrderDataGrouped;
    }
}


