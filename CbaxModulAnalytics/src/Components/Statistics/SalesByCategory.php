<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;

use Cbax\ModulAnalytics\Components\Base;

class SalesByCategory implements StatisticsInterface
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

        if (empty($parameters['categoryId'])) {
            return ['success' => true, 'gridData' => [], 'seriesData' => [], 'overall' => 0, 'overallCount' => 0];
        }

        $category = $this->getcategory($parameters['categoryId']);

        if (empty($category)) {
            return ['success' => true, 'gridData' => [], 'seriesData' => [], 'overall' => 0, 'overallCount' => 0];
        }

        if (!empty($category[0]['type']) && $category[0]['type'] == 'product') {
            $modifiedContext->setConsiderInheritance(true);
            $productCriteria = new Criteria();
            $productCriteria->addAssociation('categoriesRo');
            $productCriteria->addFilter(new EqualsFilter('product.categoriesRo.id', $parameters['categoryId']));
            $productIds = $this->productRepository->searchIds($productCriteria, $modifiedContext)->getIds();

        } elseif (
            !empty($category[0]['type']) &&
            $category[0]['type'] == 'product_stream' &&
            !empty($category[0]['productStreamId'])
        ) {
            $criteriaProductStream = new Criteria([$category[0]['productStreamId']]);
            $result = $this->productStreamRepository->searchIds($criteriaProductStream, $context)->firstId();

            if (empty($result)) {
                return ['success' => true, 'gridData' => [], 'seriesData' => [], 'overall' => 0, 'overallCount' => 0];
            }
            $filters = $this->productStreamBuilder->buildFilters($category[0]['productStreamId'], $context);
            $productCriteria = new Criteria();
            $modifiedContext->setConsiderInheritance(true);
            $productCriteria->addFilter(...$filters);
            $productIds = $this->productRepository->searchIds($productCriteria, $modifiedContext)->getIds();

        } else {
            return ['success' => true, 'gridData' => [], 'seriesData' => [], 'overall' => 0, 'overallCount' => 0];
        }

        if (empty($productIds)) {
            return ['success' => true, 'gridData' => [], 'seriesData' => [], 'overall' => 0, 'overallCount' => 0];
        }

        $productIds = UUid::fromHexToBytesList($productIds);
        $query = $this->getProductQuery($parameters, $languageId, $context, $productIds);

        $data = $query->fetchAllAssociative();

        if (empty($parameters['showVariantParent'])) {
            //$prod['optionIds'] ist hier string '["xxx","yyy"]' falls nicht null, zu array
            foreach ($data as &$prod) {
                if (!empty($prod['optionIds']) && is_string($prod['optionIds'])) {
                    $prod['optionIds'] = json_decode($prod['optionIds']);
                }
            }
            unset($prod);
            $optionNames = $this->base->getOptionsNamesFromProducts($data, $modifiedContext);
        } else {
            $parentIds = array_filter(array_unique(array_column($data, 'parentId')), function ($p) {
                return !empty($p);
            });

            if (!empty($parentIds)) {
                $parentQuery = StatisticsHelper::getParentQuery($this->connection, $parameters, $languageId, $parentIds);
                $parents = $parentQuery->fetchAllAssociativeIndexed();
                /* Alternativ mit Repositories:
                 $parents = $this->base->getProductsForOverviews($parameters, $context, [], [], $parentIds)[1] ?? [];
                 */
            } else {
                $parents = [];
            }
        }

        foreach($data as &$product) {
            $product['sum'] = (int)$product['sum'];
            $product['sales'] = round((float)$product['sales'], 2);

            if (!empty($optionNames) && !empty($product['optionIds']) && is_array($product['optionIds'])) {
                $variantOptionNames = '';
                foreach ($product['optionIds'] as $optionId) {
                    if (!empty($optionNames[$optionId])) {
                        $variantOptionNames .= ' ' . $optionNames[$optionId];
                    }
                }
                $product['name'] .= ' -' . $variantOptionNames;
            }
            unset($product['optionIds']);
            if (empty($product['parentId'])) {
                unset($product['parentId']);
            }
        }
        unset($product);

        if (!empty($parameters['showVariantParent'])) {
            foreach ($data as $key => $prod) {
                if (!empty($prod['parentId']) && !empty($parents[$prod['parentId']])) {
                    $parents[$prod['parentId']]['sum'] = ($parents[$prod['parentId']]['sum'] ?? 0) + $prod['sum'];
                    $parents[$prod['parentId']]['sales'] = ($parents[$prod['parentId']]['sales'] ?? 0) + $prod['sales'];
                    unset($data[$key]);
                }
            }
            foreach ($parents as $key => $parent) {
                if (empty($parent['sum'])) {
                    unset($parents[$key]);
                } else {
                    $parent['id'] = $key;
                }
            }

            $data = array_merge(array_values($data), array_values($parents));
            $data = StatisticsHelper::sortArrayByColumn($data, 'sales');
        }

        $overall = array_sum(array_column($data, 'sales'));
        $overallCount = array_sum(array_column($data, 'sum'));

        $seriesData = StatisticsHelper::limitData($data, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($data, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData, 'overall' => $overall, 'overallCount' => $overallCount];
    }

    private function getcategory(string $id): ?array
    {
        try {
            $idBytes = Uuid::fromHexToBytes($id);
        } catch (\Exception) {
            return NULL;
        }
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(product_stream_id)) as productStreamId',
                'product_assignment_type as type'
            ])
            ->from('`category`', 'category')
            ->andWhere('id = :id')
            ->andWhere('version_id = :versionId')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'id' => $idBytes
            ]);
        return $query->fetchAllAssociative();
    }

    private function getProductQuery(array $parameters, ?string $languageId,  Context $context, array $productIds): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(lineitems.product_id)) as `id`',
                'products.product_number as number',
                'IFNULL(IFNULL(IFNULL(trans1.name, trans2.name), trans1Parent.name), trans2Parent.name) as name',
                'SUM(lineitems.quantity) as `sum`',
                'products.option_ids as optionIds'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->leftJoin('products', 'product_translation', 'trans1',
                'products.id = trans1.product_id AND trans1.language_id = UNHEX(:language1)')
            ->leftJoin('products', 'product_translation', 'trans2',
                'products.id = trans2.product_id AND trans2.language_id = UNHEX(:language2)')
            ->leftJoin('products', 'product_translation', 'trans1Parent',
                'products.parent_id = trans1Parent.product_id AND trans1Parent.language_id = UNHEX(:language1)')
            ->leftJoin('products', 'product_translation', 'trans2Parent',
                'products.parent_id = trans2Parent.product_id AND trans2Parent.language_id = UNHEX(:language2)')
            ->andWhere('lineitems.product_id IN (:modProductIds)')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('lineitems.quantity > 0')
            ->andWhere('IF(trans1.product_version_id IS NOT NULL, trans1.product_version_id = :versionId, 1)')
            ->andWhere('IF(trans2.product_version_id IS NOT NULL, trans2.product_version_id = :versionId, 1)')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            //->andWhere('lineitems.type = :itemtype')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                //'itemtype' => 'product',
                'language1' => $languageId,
                'language2' => Defaults::LANGUAGE_SYSTEM
            ])
            ->setParameter('modProductIds', $productIds, ArrayParameterType::STRING)
            ->groupBy('`id`')
            ->orderBy('sales', 'DESC');

        if (!empty($parameters['showVariantParent'])) {
            $query->addSelect('LOWER(HEX(products.parent_id)) as parentId');
        }

        if (!empty($parameters['productSearchIds'])) {
            $parameters['productSearchIds'] = UUid::fromHexToBytesList($parameters['productSearchIds']);
            if (empty($parameters['showVariantParent'])) {
                $query->andWhere('lineitems.product_id IN (:productSearchIds)')
                    ->setParameter('productSearchIds', $parameters['productSearchIds'], ArrayParameterType::STRING);
            } else {
                $query->andWhere('lineitems.product_id IN (:productSearchIds) OR products.parent_id IN (:productSearchIds)')
                    ->setParameter('productSearchIds', $parameters['productSearchIds'], ArrayParameterType::STRING);
            }
        }

        if (!empty($parameters['manufacturerSearchIds'])) {
            $parameters['manufacturerSearchIds'] = UUid::fromHexToBytesList($parameters['manufacturerSearchIds']);
            $query->andWhere('products.manufacturer IN (:manufacturerSearchIds)')
                ->setParameter('manufacturerSearchIds', $parameters['manufacturerSearchIds'], ArrayParameterType::STRING);
        }

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross')
        {
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
}



