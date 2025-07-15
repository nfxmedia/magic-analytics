<?php

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;

use Cbax\ModulAnalytics\Components\Base;

class ProductsGridData
{
    public static function getProductsGridData(array $parameters, Context $context, string $languageId, Connection $connection, Base $base): array
    {
        $parameters['orderIds'] = UUid::fromHexToBytesList($parameters['orderIds']);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        $qb = $connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(lineitems.product_id)) as `id`',
                'products.product_number as number',
                'SUM(lineitems.quantity) as `sum`',
                'products.stock as stock',
                'products.active as active',
                'LOWER(HEX(products.parent_id)) as parentId',
                'LOWER(HEX(products.manufacturer)) as manufacturerId',
                "SUM(
                    IF(
                        orders.tax_status = 'gross' OR orders.tax_status = 'tax-free',
                        lineitems.total_price/orders.currency_factor,
                        (((JSON_EXTRACT(lineitems.price,'$.taxRules[0].taxRate')/100)+1)*lineitems.total_price)/orders.currency_factor
                    )
                )
                as gross",
                "SUM(
                    IF(
                        orders.tax_status = 'net' OR orders.tax_status = 'tax-free',
                        lineitems.total_price/orders.currency_factor,
                        (lineitems.total_price/((JSON_EXTRACT(lineitems.price,'$.taxRules[0].taxRate')/100)+1))/orders.currency_factor
                    )
                )
                as net"
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->leftJoin('products', 'product_translation', 'trans1', 'products.id = trans1.product_id AND trans1.language_id = UNHEX(:language1)')
            ->andWhere('lineitems.version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('lineitems.order_id IN (:orderIds)')
            ->andWhere('IF(trans1.product_version_id IS NOT NULL, trans1.product_version_id = :versionId, 1)')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'language1' => $languageId
            ])
            ->setParameter('orderIds', $parameters['orderIds'], ArrayParameterType::STRING)
            ->groupBy('`id`')
            ->orderBy('sum', 'DESC');

        if (!empty($parameters['id']) && !empty($parameters['statistics'])) {

            if ($parameters['statistics'] === 'sales_by_manufacturer') {
                $manufacturerId = UUid::fromHexToBytes($parameters['id']);
                $query->andWhere('products.manufacturer = :manufacturerId')
                    ->setParameter('manufacturerId', $manufacturerId);
            }
        }

        if (empty($parameters['showParents'])) {
            $query->addSelect('products.option_ids as optionIds');
        }

        if ($languageId === Defaults::LANGUAGE_SYSTEM) {
            $query->addSelect('trans1.name as name');
        } else {
            $query->addSelect('IFNULL(trans1.name, trans2.name) as name')
                ->leftJoin('products', 'product_translation', 'trans2',
                    'products.id = trans2.product_id AND trans2.language_id = UNHEX(:language2)')
                ->andWhere('IF(trans2.product_version_id IS NOT NULL, trans2.product_version_id = :versionId, 1)')
                ->setParameter('language2', Defaults::LANGUAGE_SYSTEM);
        }

        $data = $query->fetchAllAssociative();

        $manufacturerIds = array_column($data, 'manufacturerId');
        $parentIds = array_column($data, 'parentId');
        $parentIds = array_filter(array_unique($parentIds), function ($p) {
            return !empty($p);
        });

        if (!empty($parentIds)) {
            $parentQuery = StatisticsHelper::getParentQuery($connection, $parameters, $languageId, $parentIds);
            $parents = $parentQuery->fetchAllAssociativeIndexed();
            $manufacturerIds = array_merge($manufacturerIds, array_column($parents, 'manufacturerId'));
        } else $parents = [];

        $manufacturerIds = array_filter(array_unique($manufacturerIds), function ($m) {
            return !empty($m);
        });
        $manufacturerNames = self::getManufacturerNamesFromIds($connection, $manufacturerIds, $languageId);

        //Varianten werden angezeigt - default
        if (empty($parameters['showParents'])) {
            foreach ($data as &$prod) {
                if (!empty($prod['optionIds']) && is_string($prod['optionIds'])) {
                    $prod['optionIds'] = json_decode($prod['optionIds']);
                }
            }
            unset($prod);

            $optionNames = $base->getOptionsNamesFromProducts($data, $modifiedContext);

            foreach ($data as &$product) {
                $product['gross'] = round($product['gross'], 2);
                $product['net'] = round($product['net'], 2);
                $product['gprice'] = !empty($product['sum']) ? round($product['gross']/$product['sum'],2) : 'NA';
                $product['nprice'] = !empty($product['sum']) ? round($product['net']/$product['sum'],2) : 'NA';

                if (empty($product['parentId'])) {
                    $product['manufacturerName'] = $manufacturerNames[$product['manufacturerId']] ?? '';
                } elseif (!empty($parents[$product['parentId']])) {
                    $product['name'] ??= $parents[$product['parentId']]['name'] ?? '';
                    $product['active'] = is_null($product['active']) ? ($parents[$product['parentId']]['active'] ?? 0) : $product['active'];
                    $product['manufacturerName'] = is_null($product['manufacturerId']) ? ($manufacturerNames[$parents[$product['parentId']]['manufacturerId']] ?? '') : $manufacturerNames[$product['manufacturerId']] ?? '';

                    if (!empty($optionNames) && !empty($product['optionIds']) && is_array($product['optionIds'])) {
                        $variantOptionNames = '';
                        foreach ($product['optionIds'] as $optionId) {
                            if (!empty($optionNames[$optionId])) {
                                $variantOptionNames .= ' ' . $optionNames[$optionId];
                            }
                        }
                        $product['name'] .= ' -' . $variantOptionNames;
                    }
                }

                unset($product['optionIds']);
                unset($product['parentId']);
            }
            unset($product);

            //Variantendaten werden im parent zusammengefasst
        } else {
            foreach ($parents as $key => &$parent) {
                $parent['id'] = $key;
                $parent['name'] .= ' (' . $parent['childCount'] . ')';
                $parent['manufacturerName'] = $manufacturerNames[$parent['manufacturerId']] ?? '';
                $parent['sum'] = 0;
                $parent['gross'] = 0;
                $parent['net'] = 0;
                $parent['stock'] = 0;
            }
            unset($parent);

            foreach ($data as $key => &$product) {
                if (empty($product['parentId'])) {
                    $product['gross'] = round($product['gross'], 2);
                    $product['net'] = round($product['net'], 2);
                    $product['gprice'] = !empty($product['sum']) ? round($product['gross']/$product['sum'],2) : 'NA';
                    $product['nprice'] = !empty($product['sum']) ? round($product['net']/$product['sum'],2) : 'NA';
                    $product['manufacturerName'] = $manufacturerNames[$product['manufacturerId']] ?? '';
                    unset($product['parentId']);
                } elseif (!empty($parents[$product['parentId']])) {
                    $parents[$product['parentId']]['sum'] += $product['sum'];
                    $parents[$product['parentId']]['stock'] += $product['stock'];
                    $parents[$product['parentId']]['gross'] += $product['gross'];
                    $parents[$product['parentId']]['net'] += $product['net'];
                    unset($data[$key]);
                }
            }

            foreach ($parents as $key => &$parent) {
                if (empty($parent['sum'])) {
                    unset($parents[$key]);
                } else {
                    $parent['gprice'] = round($parent['gross']/$parent['sum'], 2);
                    $parent['nprice'] = round($parent['net']/$parent['sum'], 2);
                    $parent['gross'] = round($parent['gross'], 2);
                    $parent['net'] = round($parent['net'], 2);
                }
            }
            unset($parent);

            $data = array_merge($data, array_values($parents));
            $data = StatisticsHelper::sortArrayByColumn($data);
        }

        return  ['success' => true, 'gridData' => $data];
    }

    private static function getManufacturerNamesFromIds(Connection $connection, array $manufacturerIds, string $languageId): array
    {
        if (empty($manufacturerIds)) return [];

        $qb = $connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(translations.product_manufacturer_id)) as manufacturerId',
                'LOWER(HEX(translations.language_id)) as languageId',
                'name as `name`'
            ])
            ->from('product_manufacturer_translation', 'translations')
            ->andWhere('translations.product_manufacturer_version_id = UNHEX(:versionId)')
            ->andWhere('HEX(translations.product_manufacturer_id) IN (:manufacturerIds)')
            ->andWhere('translations.language_id = UNHEX(:language1) OR translations.language_id = UNHEX(:language2)')
            ->setParameters([
                'versionId' => Defaults::LIVE_VERSION,
                'language2' => Defaults::LANGUAGE_SYSTEM,
                'language1' => $languageId
            ])
            ->setParameter('manufacturerIds', $manufacturerIds, ArrayParameterType::STRING);

        $data = $query->fetchAllAssociative();

        $names = [];
        foreach ($data as $manufacturer) {
            if (empty($names[$manufacturer['manufacturerId']])) {
                $names[$manufacturer['manufacturerId']] = $manufacturer['name'];
            } elseif ($manufacturer['languageId'] === $languageId && $manufacturer['languageId'] !== Defaults::LANGUAGE_SYSTEM) {
                $names[$manufacturer['manufacturerId']] = $manufacturer['name'];
            }
        }

        return $names;
    }

}
