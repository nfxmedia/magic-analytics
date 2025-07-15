<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;

use Cbax\ModulAnalytics\Components\Base;

class CategoryCompare implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $productRepository,
        private readonly Connection $connection,
        private readonly EntityRepository $productStreamRepository,
        private readonly ProductStreamBuilder $productStreamBuilder
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);
        $modifiedContext->setConsiderInheritance(true);

        if (empty($parameters['categories'])) {
            return ['success' => true, 'seriesData' => []];
        }

        $categories = $this->getcategories($parameters['categories']);
        if (empty($categories)) {
            return ['success' => true, 'seriesData' => []];
        }

        if (!empty($parameters['productSearchIds'])) {
            $parameters['productSearchIds'] = UUid::fromHexToBytesList($parameters['productSearchIds']);
        }

        if (!empty($parameters['manufacturerSearchIds'])) {
            $parameters['manufacturerSearchIds'] = UUid::fromHexToBytesList($parameters['manufacturerSearchIds']);
        }

        $data = [];
        foreach ($categories as $category) {
            if (!empty($category['type']) && $category['type'] == 'product') {
                $productCriteria = new Criteria();
                $productCriteria->addAssociation('categoriesRo');
                $productCriteria->addFilter(new EqualsFilter('product.categoriesRo.id', $category['id']));
                $productIds = $this->productRepository->searchIds($productCriteria, $modifiedContext)->getIds();

            } elseif (
                !empty($category['type']) &&
                $category['type'] == 'product_stream' &&
                !empty($category['productStreamId'])
            ) {
                $criteriaProductStream = new Criteria([$category['productStreamId']]);
                $result = $this->productStreamRepository->searchIds($criteriaProductStream, $context)->firstId();

                if (empty($result)) {
                    return ['success' => true, 'gridData' => [], 'seriesData' => [], 'overall' => 0, 'overallCount' => 0];
                }
                $filters = $this->productStreamBuilder->buildFilters($category['productStreamId'], $context);
                $productCriteria = new Criteria();
                $modifiedContext->setConsiderInheritance(true);
                $productCriteria->addFilter(...$filters);
                $productIds = $this->productRepository->searchIds($productCriteria, $modifiedContext)->getIds();

            } else {
                continue;
            }

            if (empty($productIds)) {
                $data[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'sales' => 0,
                    'sum' => 0
                ];
                continue;
            }

            $productIds = UUid::fromHexToBytesList($productIds);

            $query = $this->getOrderQuery($parameters, $productIds, $context);

            $catData = $query->fetchAllAssociative();

            if (!empty($catData)) {
                $data[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'sales' => round((float)$catData[0]['sales'],2),
                    'sum' => (int)$catData[0]['sum']
                ];
            }
        }

        $data = StatisticsHelper::sortArrayByColumn($data);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $data];
    }

    private function getOrderQuery(array $parameters, array $productIds, Context $context): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'SUM(lineitems.quantity) as `sum`'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->andWhere('lineitems.product_id IN (:modProductIds)')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('lineitems.quantity > 0')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->andWhere('products.version_id = :versionId')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
            ])
            ->setParameter('modProductIds', $productIds, ArrayParameterType::STRING);

        if (!empty($parameters['productSearchIds'])) {
            $query->andWhere('lineitems.product_id IN (:productSearchIds)')
                ->setParameter('productSearchIds', $parameters['productSearchIds'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['manufacturerSearchIds'])) {
            $query->andWhere('products.manufacturer IN (:manufacturerSearchIds)')
                ->setParameter('manufacturerSearchIds', $parameters['manufacturerSearchIds'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross') {
            $query->addSelect([
                "SUM(
                    IF(
                        orders.tax_status = 'gross' OR orders.tax_status = 'tax-free',
                        lineitems.total_price/orders.currency_factor,
                        (((JSON_EXTRACT(lineitems.price,'$.taxRules[0].taxRate')/100)+1)*lineitems.total_price)/orders.currency_factor
                        )
                    )
                    as sales"
            ]);
        } else {

            $query->addSelect([
                "SUM(
                    IF(
                        orders.tax_status = 'net' OR orders.tax_status = 'tax-free',
                        lineitems.total_price/orders.currency_factor,
                        (lineitems.total_price/((JSON_EXTRACT(lineitems.price,'$.taxRules[0].taxRate')/100)+1))/orders.currency_factor
                        )
                    )
                    as sales"
            ]);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        return $query;
    }

    private function getcategories(array $categories): ?array
    {
        try {
            $idBytes = Uuid::fromHexToBytesList(array_column($categories, 'id'));
        } catch (\Exception) {
            return NULL;
        }
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(id)) as id',
                'LOWER(HEX(product_stream_id)) as productStreamId',
                'product_assignment_type as type'
            ])
            ->from('`category`', 'category')
            ->andWhere('id IN (:ids)')
            ->andWhere('version_id = :versionId')
            ->setParameters(
                [
                    'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    'ids' => $idBytes
                ],
                [
                    'ids' => ArrayParameterType::STRING
                ]);
        $data = $query->fetchAllAssociativeIndexed();

        foreach ($categories as $cat) {
            if (!empty($data[$cat['id']])) {
                $data[$cat['id']]['id'] = $cat['id'];
                $data[$cat['id']]['name'] = $cat['name'];
            }
        }

        return $data;
    }
}
