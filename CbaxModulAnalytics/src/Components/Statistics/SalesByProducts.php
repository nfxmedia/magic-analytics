<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Cbax\ModulAnalytics\Components\Base;

class SalesByProducts implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly Connection $connection,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $manufacturerRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);
        $sortingField = $parameters['sortBy'] ?? 'sales';
        $direction = $parameters['sortDirection'] ?? 'DESC';

        $query = $this->getProductQuery($parameters, $languageId, $context);
        $data = $query->fetchAllAssociative();

        if (empty($data)) {
            return ['success' => true, 'gridData' => [], 'seriesData' => []];
        }

        $manufacturerIds = array_filter(array_unique(array_column($data, 'manufacturerId')));

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
                $manufacturerIds = array_filter(array_unique(array_merge($manufacturerIds, array_column($parents, 'manufacturerId'))));
                /* Alternativ mit Repositories:
                 $parents = $this->base->getProductsForOverviews($parameters, $context, [], [], $parentIds)[1] ?? [];
                 */
            } else {
                $parents = [];
            }
        }

        if (!empty($manufacturerIds)) {
            $manufacturerCriteria = new Criteria($manufacturerIds);
            $manufacturers = $this->manufacturerRepository->search($manufacturerCriteria, $modifiedContext)->getElements();
        } else {
            $manufacturers = [];
        }

        //Anzahl Bestellungen mit Produkt
        $modifiedContext->setConsiderInheritance(true);
        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);
        $criteria->addAssociation('lineItems');

        $filters[] = new EqualsAnyFilter('order.lineItems.productId', array_column($data, 'id'));

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-orders-by-product',
                new TermsAggregation(
                    'orders-by-product',
                    'order.lineItems.productId'
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $modifiedContext);
        $aggregation = $result->getAggregations()->get('orders-by-product');

        $orderNumberData = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            if (empty($key)) continue;
            $orderNumberData[$key] = (int)$bucket->getCount();
        }
        ///////////////////////////////

        foreach($data as &$product) {
            $product['sum'] = (int)$product['sum'];
            $product['stock'] = (int)$product['stock'];
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
            $product['orderCount'] = $orderNumberData[$product['id']] ?? null;
            $product['priceAV'] = round($product['sales']/$product['sum'], 2);
            if (!empty($product['manufacturerId'])) {
                $product['manufacturer'] = $manufacturers[$product['manufacturerId']] ? $manufacturers[$product['manufacturerId']]->getTranslated()['name'] : '';
            } else {
                $product['manufacturer'] = '';
            }

            unset($product['manufacturerId']);
        }
        unset($product);

        if (!empty($parameters['showVariantParent'])) {
            //Parent Daten ermitteln
            $parentdata = [];
            foreach ($data as $key => $prod) {
                if (!empty($prod['parentId']) && !empty($parents[$prod['parentId']])) {
                    if (empty($parentdata[$prod['parentId']])) {
                        if (empty($prod['sum'])) {
                            unset($data[$key]);
                            continue;
                        }
                        $parentdata[$prod['parentId']] = [
                            'id' => $prod['parentId'],
                            'number' => $parents[$prod['parentId']]['number'],
                            'name' => $parents[$prod['parentId']]['name'] . ' ('. $parents[$prod['parentId']]['childCount'] . ')',
                            'sum' => $prod['sum'],
                            'stock' => '-',
                            'sales' => $prod['sales'],
                            'orderCount' => $prod['orderCount'],
                            'priceAV' => round($prod['sales']/$prod['sum'], 2),
                            'manufacturer' => $manufacturers[$parents[$prod['parentId']]['manufacturerId']] ? $manufacturers[$parents[$prod['parentId']]['manufacturerId']]->getTranslated()['name'] : ''
                        ];

                    } else {
                        $parentdata[$prod['parentId']]['sum'] += $prod['sum'];
                        $parentdata[$prod['parentId']]['sales'] += $prod['sales'];
                        $parentdata[$prod['parentId']]['orderCount'] += $prod['orderCount'];
                        $parentdata[$prod['parentId']]['priceAV'] = round($parentdata[$prod['parentId']]['sales']/$parentdata[$prod['parentId']]['sum'], 2);
                    }
                    unset($data[$key]);
                }
            }

            $data = array_merge(array_values($data), array_values($parentdata));
            //parents einsortieren
            $data = StatisticsHelper::sortArrayByColumn($data, $sortingField, $direction);
        }

        elseif (in_array($sortingField, ['orderCount','priceAV', 'manufacturer'])) {
            //Sortierung der anderen Felder bereits im SQL
            $data = StatisticsHelper::sortArrayByColumn($data, $sortingField, $direction);
        }

        $seriesData = StatisticsHelper::limitData($data, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($data, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData];
    }

    private function getProductQuery(array $parameters, ?string $languageId, Context $context): QueryBuilder
    {
        $sortingField = $parameters['sortBy'] ?? 'sales';
        $sortingField = in_array($sortingField, ['stock', 'number','name','sum','sales']) ? $sortingField : 'sales';
        $direction = $parameters['sortDirection'] ?? 'DESC';

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(lineitems.product_id)) as `id`',
                'products.product_number as number',
                'IFNULL(IFNULL(IFNULL(trans1.name, trans2.name), trans1Parent.name), trans2Parent.name) as name',
                'SUM(lineitems.quantity) as `sum`',
                'products.option_ids as optionIds',
                'products.stock as stock',
                'LOWER(HEX(products.manufacturer)) as manufacturerId'
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
            ->andWhere('lineitems.version_id = :versionId')
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
            ->groupBy('`id`')
            ->orderBy($sortingField, $direction);

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





