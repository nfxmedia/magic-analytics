<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

use Cbax\ModulAnalytics\Components\Base;

class ProductImpressions implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $productImpressionRepository,
        private readonly Connection $connection,
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $context = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        //login Besucher
        $criteria = StatisticsHelper::getBaseCriteria('date', $parameters, false);

        $filters = [];
        if (!empty($parameters['productSearchIds']) && empty($parameters['showVariantParent'])) {
            $filters[] = new EqualsAnyFilter('productId', $parameters['productSearchIds']);
        }

        if (!empty($parameters['customerGroupIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('customerGroupId', $parameters['customerGroupIds']));
        } else {
            $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('customerGroupId', null)
                ]
            ));
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-count-by-product',
                new TermsAggregation(
                    'count-by-product',
                    'productId',
                    null,
                    null,
                    new SumAggregation('sum-impressions', 'impressions')
                ),
                $filters
            )
        );

        $result1 = $this->productImpressionRepository->search($criteria, $context);
        $aggregation1 = $result1->getAggregations()->get('count-by-product');
        //////////////////////

        //not login Besucher
        $criteria = StatisticsHelper::getBaseCriteria('date', $parameters, false);
        $criteria->addFilter(new EqualsFilter('customerGroupId', null));

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-count-by-product',
                new TermsAggregation(
                    'count-by-product',
                    'productId',
                    null,
                    null,
                    new SumAggregation('sum-impressions', 'impressions')
                ),
                $filters
            )
        );

        $result2 = $this->productImpressionRepository->search($criteria, $context);
        $aggregation2 = $result2->getAggregations()->get('count-by-product');
        ///////////////////

        //product Daten Name, ProductNumber holen
        $productIds = array_unique(array_merge($aggregation1->getKeys(), $aggregation2->getKeys()));
        [$products, $parents] = $this->base->getProductsBasicData($parameters, $context, $productIds);

        //verkäufe holen/////
        $query = $this->getProductQuery($parameters, $languageId, $context, $productIds);
        $salesData = $query->fetchAllKeyValue();

        $data = [];
        foreach ($aggregation1->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            if (empty($key) || empty($products[$key])) continue;

            if (empty($parameters['showVariantParent']) || empty($products[$key]['parentId'])) {
                $data[$key] = [
                    'id' => $key,
                    'number' => $products[$key]['number'],
                    'name' => $products[$key]['name'],
                    'sum1' => (int)$bucket->getResult()->getSum(),
                    'sum2' => 0,
                    'sum' => (int)$bucket->getResult()->getSum(),
                    'sold' => (int)($salesData[$key] ?? 0)
                ];
            } elseif (!empty($parents[$products[$key]['parentId']])) {
                $parents[$products[$key]['parentId']]['sum1'] = ($parents[$products[$key]['parentId']]['sum1'] ?? 0) + (int)$bucket->getResult()->getSum();
                $parents[$products[$key]['parentId']]['sum'] = ($parents[$products[$key]['parentId']]['sum'] ?? 0) + (int)$bucket->getResult()->getSum();
                $parents[$products[$key]['parentId']]['sum2'] = 0;
                $parents[$products[$key]['parentId']]['sold'] = ($parents[$products[$key]['parentId']]['sold'] ?? 0) + (int)($salesData[$key] ?? 0);
            }
        }

        foreach ($aggregation2->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            if (empty($key)) continue;

            if (!empty($data[$key])) {
                $data[$key]['sum2'] = (int)$bucket->getResult()->getSum();
                $data[$key]['sum'] = $data[$key]['sum2'] + $data[$key]['sum1'];
                continue;
            }

            if (!empty($products[$key])) {
                if (empty($parameters['showVariantParent']) || empty($products[$key]['parentId'])) {
                    $data[$key] = [
                        'id' => $key,
                        'number' => $products[$key]['number'],
                        'name' => $products[$key]['name'],
                        'sum1' => 0,
                        'sum2' => (int)$bucket->getResult()->getSum(),
                        'sum' => (int)$bucket->getResult()->getSum(),
                        'sold' => (int)($salesData[$key] ?? 0)
                    ];
                } elseif (!empty($parents[$products[$key]['parentId']])) {
                    $parents[$products[$key]['parentId']]['sum1'] = ($parents[$products[$key]['parentId']]['sum1'] ?? 0);
                    $parents[$products[$key]['parentId']]['sum'] = ($parents[$products[$key]['parentId']]['sum'] ?? 0) + (int)$bucket->getResult()->getSum();
                    $parents[$products[$key]['parentId']]['sum2'] = ($parents[$products[$key]['parentId']]['sum2'] ?? 0) + (int)$bucket->getResult()->getSum();
                    $parents[$products[$key]['parentId']]['sold'] = ($parents[$products[$key]['parentId']]['sold'] ?? 0) + (int)($salesData[$key] ?? 0);
                }
            }
        }

        $data = array_merge(array_values($data), array_values($parents));
        foreach ($data as $id => $prod) {
            $data[$id]['conversion'] = round((100*$data[$id]['sold']) / $data[$id]['sum'],2);
        }
        $overall = array_sum(array_column($data, 'sum'));
        $sortedData = StatisticsHelper::sortArrayByColumn($data);
        $seriesData = StatisticsHelper::limitData($sortedData, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData, 'overall' => $overall];
    }

    private function getProductQuery(array $parameters, ?string $languageId, Context $context, array $productIds): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(lineitems.product_id)) as `id`',
                'SUM(lineitems.quantity) as `sum`'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->andWhere('lineitems.version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('lineitems.product_id IN (:productids)')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('lineitems.quantity > 0')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'productids' => UUid::fromHexToBytesList($productIds),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
            ], [
                'productids' => ArrayParameterType::STRING
            ])
            ->groupBy('`id`');

        if (!empty($parameters['productSearchIds'])) {
            $parameters['productSearchIds'] = UUid::fromHexToBytesList($parameters['productSearchIds']);
            $query->andWhere('lineitems.product_id IN (:productSearchIds)')
                ->setParameter('productSearchIds', $parameters['productSearchIds'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['manufacturerSearchIds'])) {
            $parameters['manufacturerSearchIds'] = UUid::fromHexToBytesList($parameters['manufacturerSearchIds']);
            $query->andWhere('products.manufacturer IN (:manufacturerSearchIds)')
                ->setParameter('manufacturerSearchIds', $parameters['manufacturerSearchIds'], ArrayParameterType::STRING);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        return $query;
    }
}
