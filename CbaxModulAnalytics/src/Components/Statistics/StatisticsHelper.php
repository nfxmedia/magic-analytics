<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\BucketResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Context;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class StatisticsHelper
{
    public static function getFormatedDate(mixed $date, string $adminLocalLanguage = 'de-DE'): ?string
    {
        if (is_string($date))
        {
            if ($adminLocalLanguage === 'de-DE')
            {
                return \DateTime::createFromFormat('Y-m-d', explode(' ', $date)[0])?->format('d.m.Y');
            }
            else
            {
                return \DateTime::createFromFormat('Y-m-d', explode(' ', $date)[0])?->format('d/m/Y');
            }
        } else {

            if ($adminLocalLanguage === 'de-DE')
            {
                return $date?->format('d.m.Y');
            }
            else
            {
                return $date?->format('d/m/Y');
            }
        }
    }

    // for category-type statistics with possible to many datapoints for charts to handle
    public static function limitData(array $data, int $limit = 100): array
    {
        if (count($data) <= $limit) {
            return $data;
        }

        $limitedArray = array_slice($data, 0, $limit);

        $restArray = array_slice($data, $limit);

        $restArraySumColumn = array_column($restArray, 'sum');
        $restSum = array_sum($restArraySumColumn);

        if (empty($data[0]['number'])) {

            $element = ['name' => 'cbax-analytics.data.others', 'sum' => $restSum];

        } else {

            $element = ['id' => '', 'number' => '', 'name' => 'cbax-analytics.data.others', 'sum' => $restSum];
        }

        foreach (['sales', 'count', 'sum1', 'sum2'] as $columnName)
        {
            if (!empty($data[0][$columnName]))
            {
                $restArrayColumn = array_column($restArray, $columnName);
                $element[$columnName] = array_sum($restArrayColumn);
            }
        }

        $limitedArray[] = $element;

        return $limitedArray;
    }

    public static function sortArrayByColumn(array $array, string $columnName = 'sum', string $direction = 'DESC'): array
    {
        if (count($array) == 0) return $array;

        usort($array, function($a, $b) use ($columnName, $direction)
        {
            if ($a[$columnName] == $b[$columnName])
            {
                return 0;
            }

            if ($direction === 'DESC') {
                return ($a[$columnName] > $b[$columnName]) ? -1 : 1;

            } else {
                return ($a[$columnName] < $b[$columnName]) ? -1 : 1;
            }
        });

        return $array;
    }

    public static function calculateAmountInSystemCurrency(BucketResult $currencyAggregation): int|float
    {
        $amount = 0;
        foreach ($currencyAggregation->getBuckets() as $bucket)
        {
            if ($bucket->getKey() == 0) continue;
            $amount += $bucket->getResult()->getSum() / $bucket->getKey();
        }

        return $amount;
    }

    public static function getBaseCriteria(string $dateColumn, array $parameters, bool $forOrders = true): Criteria
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new RangeFilter($dateColumn, [
                RangeFilter::GTE => $parameters['startDate'],
                RangeFilter::LTE => $parameters['endDate']
            ])
        );

        if (!empty($parameters['salesChannelIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        if ($forOrders && !empty($parameters['customerGroupIds']))
        {
            $criteria->addAssociation('orderCustomer');
            $criteria->getAssociation('orderCustomer')->addAssociation('customer');
            $criteria->addFilter(new EqualsAnyFilter('orderCustomer.customer.groupId', $parameters['customerGroupIds']));
        }

        if ($forOrders && !empty($parameters['affiliateCodes']))
        {
            $criteria->addFilter(new EqualsAnyFilter('affiliateCode', $parameters['affiliateCodes']));
        }
        if ($forOrders && !empty($parameters['notaffiliateCodes']))
        {
            $criteria->addFilter(
                new MultiFilter(
                    MultiFilter::CONNECTION_OR,
                    [
                        new EqualsFilter('affiliateCode', NULL),
                        new NotFilter(
                            NotFilter::CONNECTION_OR,
                            [
                                new EqualsAnyFilter('affiliateCode', $parameters['notaffiliateCodes'])
                            ]
                        )
                    ]
                )
            );
        }

        if ($forOrders && !empty($parameters['campaignCodes']))
        {
            $criteria->addFilter(new EqualsAnyFilter('campaignCode', $parameters['campaignCodes']));
        }

        if ($forOrders && !empty($parameters['promotionCodes']))
        {
            $criteria->addFilter(new EqualsAnyFilter('lineItems.payload.code', $parameters['promotionCodes']));
        }

        if ($forOrders && !empty($parameters['blacklistedStatesIds']['order']))
        {
            $criteria->addFilter(
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    [
                        new EqualsAnyFilter('stateId', $parameters['blacklistedStatesIds']['order'])
                    ]
                )
            );
        }

        if ($forOrders && !empty($parameters['blacklistedStatesIds']['transaction']))
        {
            $criteria->addAssociation('transactions');
        }

        if ($forOrders && !empty($parameters['blacklistedStatesIds']['delivery']))
        {
            $criteria->addAssociation('deliveries');
        }

        return $criteria;
    }

    public static function getBaseCriteriaWithOrderIds(string $dateColumn, array $parameters, array $orderIds): Criteria
    {
        $criteria = new Criteria($orderIds);
        $criteria->setLimit(1);
        $criteria->addFilter(
            new RangeFilter($dateColumn, [
                RangeFilter::GTE => $parameters['startDate'],
                RangeFilter::LTE => $parameters['endDate']
            ])
        );

        if (!empty($parameters['salesChannelIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        if (!empty($parameters['customerGroupIds']))
        {
            $criteria->addAssociation('orderCustomer');
            $criteria->getAssociation('orderCustomer')->addAssociation('customer');
            $criteria->addFilter(new EqualsAnyFilter('orderCustomer.customer.groupId', $parameters['customerGroupIds']));
        }

        if (!empty($parameters['affiliateCodes']))
        {
            $criteria->addFilter(new EqualsAnyFilter('affiliateCode', $parameters['affiliateCodes']));
        }

        if (!empty($parameters['campaignCodes']))
        {
            $criteria->addFilter(new EqualsAnyFilter('campaignCode', $parameters['campaignCodes']));
        }

        if (!empty($parameters['promotionCodes']))
        {
            $criteria->addFilter(new EqualsAnyFilter('lineItems.payload.code', $parameters['promotionCodes']));
        }

        if (!empty($parameters['blacklistedStatesIds']['order']))
        {
            $criteria->addFilter(
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    [
                        new EqualsAnyFilter('stateId', $parameters['blacklistedStatesIds']['order'])
                    ]
                )
            );
        }

        if (!empty($parameters['blacklistedStatesIds']['transaction']))
        {
            $criteria->addAssociation('transactions');
        }

        if (!empty($parameters['blacklistedStatesIds']['delivery']))
        {
            $criteria->addAssociation('deliveries');
        }

        return $criteria;
    }

    public static function getMoreFilters(array $parameters): array
    {
        if (empty($parameters['blacklistedStatesIds']['transaction']))
        {
            $filters = [];
        } else {
            $filters = [
                new NotFilter(
                    NotFilter::CONNECTION_OR,
                    [
                        new EqualsAnyFilter('transactions.stateMachineState.technicalName', ['cancelled', 'failed']),
                        new EqualsAnyFilter('transactions.stateId', $parameters['blacklistedStatesIds']['transaction'])
                    ]
                )
            ];
        }

        //Bei Orders mit Promotion Rabatt auf Versandkosten extra Zeile in deliveries mit negativen VK ausfiltern
        if (!empty($parameters['blacklistedStatesIds']['delivery']))
        {
            $filters[] = new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new ContainsFilter('deliveries.shippingCosts', '-'),
                    new EqualsAnyFilter('deliveries.stateId', $parameters['blacklistedStatesIds']['delivery'])
                ]
            );
        }

        return $filters;
    }

    public static function getLanguageModifiedContext(Context $context, string $languageId): Context
    {
        if (empty($languageId)) return $context;

        $context = $context->assign(['languageIdChain' => array_filter(array_unique(array_merge([$languageId], $context->getLanguageIdChain())))]);

        return $context;
    }

    public static function getDatesFromRange(string $start, string $end, string $format = 'Y-m-d'): array
    {
        $array = [];
        $interval = new \DateInterval('P1D');

        $realEnd = new \DateTime($end);
        $realEnd->add($interval);

        $period = new \DatePeriod(new \DateTime($start), $interval, $realEnd);

        foreach ($period as $date) {
            $array[] = $date?->format($format);
        }

        return $array;
    }

    public static function getParentQuery(Connection $connection, array $parameters, ?string $languageId, array $parentIds): QueryBuilder
    {
        $qb = $connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(products.id)) as id',
                'products.product_number as number',
                'IFNULL(trans1.name, trans2.name) as name',
                'products.active as active',
                'products.child_count as childCount',
                'LOWER(HEX(products.manufacturer)) as manufacturerId'
            ])
            ->from('product', 'products')
            ->leftJoin('products', 'product_translation', 'trans1',
                'products.id = trans1.product_id AND trans1.language_id = UNHEX(:language1)')
            ->leftJoin('products', 'product_translation', 'trans2',
                'products.id = trans2.product_id AND trans2.language_id = UNHEX(:language2)')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('products.child_count > 0')
            ->andWhere('IF(trans1.product_version_id IS NOT NULL, trans1.product_version_id = :versionId, 1)')
            ->andWhere('IF(trans2.product_version_id IS NOT NULL, trans2.product_version_id = :versionId, 1)')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'language1' => $languageId,
                'language2' => Defaults::LANGUAGE_SYSTEM
            ]);

        if (!empty($parentIds)) {
            $parentIds = UUid::fromHexToBytesList($parentIds);
            $query->andWhere('products.id IN (:parentIds)')
                ->setParameter('parentIds', $parentIds, ArrayParameterType::STRING);
        }

        if (!empty($parameters['productSearchIds'])) {
            $parameters['productSearchIds'] = UUid::fromHexToBytesList($parameters['productSearchIds']);
            $query->andWhere('products.id IN (:productSearchIds)')
                ->setParameter('productSearchIds', $parameters['productSearchIds'], ArrayParameterType::STRING);
        }

        return $query;
    }

    public static function getCartTokenQuery(Connection $connection, array $parameters): QueryBuilder
    {
        $qb = $connection->createQueryBuilder();

        $tokenQuery = $qb
            ->select([
                'context.token as token'
            ])
            ->from('`sales_channel_api_context`', 'context')
            ->InnerJoin('context', 'cart', 'cart', 'context.token = cart.token')
            ->andWhere('context.updated_at >= :start')
            ->andWhere('context.updated_at <= :end')
            ->andWhere('context.customer_id IS NOT NULL')
            ->andWhere('context.sales_channel_id IS NOT NULL')
            ->setParameters([
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
            ]);

        if (!empty($parameters['salesChannelIds'])) {
            $scids = UUid::fromHexToBytesList($parameters['salesChannelIds']);

            $tokenQuery->andWhere('context.sales_channel_id IN (:salesChannels)')
                ->setParameter('salesChannels', $scids, ArrayParameterType::STRING);
        }

        if (!empty($parameters['customerGroupIds'])) {
            $cgids = UUid::fromHexToBytesList($parameters['customerGroupIds']);

            $tokenQuery->InnerJoin('context', 'customer', 'customer', 'context.customer_id = customer.id')
                ->andWhere('customer.customer_group_id IN (:customerGroupIds)')
                ->setParameter('customerGroupIds', $cgids,  ArrayParameterType::STRING);
        }

        return $tokenQuery;
    }

    //for later
    public static function getLineItemQuery(Connection $connection, array $parameters, ?string $languageId, array $includes = []): QueryBuilder
    {
        $qb = $connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(lineitems.product_id)) as `id`',
                'products.product_number as number',
                'SUM(lineitems.quantity) as `sum`',
                'LOWER(HEX(products.parent_id)) as parentId'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->leftJoin('products', 'product_translation', 'trans1',
                'products.id = trans1.product_id AND trans1.language_id = UNHEX(:language1)')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('lineitems.quantity > 0')
            ->andWhere('IF(trans1.product_version_id IS NOT NULL, trans1.product_version_id = :versionId, 1)')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'language1' => $languageId
            ])
            ->groupBy('`id`');

        if ($languageId === Defaults::LANGUAGE_SYSTEM) {
            $query->addSelect('trans1.name as name');
        } else {
            $query->addSelect('IFNULL(trans1.name, trans2.name) as name')
                ->leftJoin('products', 'product_translation', 'trans2',
                    'products.id = trans2.product_id AND trans2.language_id = UNHEX(:language2)')
                ->andWhere('IF(trans2.product_version_id IS NOT NULL, trans2.product_version_id = :versionId, 1)')
                ->setParameter('language2', Defaults::LANGUAGE_SYSTEM);
        }

        if (empty($parameters['showParents'])) {
            $query->addSelect('products.option_ids as optionIds');
        }

        if (in_array('manufacturerId', $includes)) {
            $query->addSelect('LOWER(HEX(products.manufacturer)) as manufacturerId');
        }
        if (in_array('stock', $includes)) {
            $query->addSelect('products.stock as stock');
        }
        if (in_array('active', $includes)) {
            $query->addSelect('products.active as active');
        }
        if (in_array('dateRange', $includes) && !empty($parameters['startDate']) && !empty($parameters['endDate'])) {
            $query->andWhere('orders.order_date_time >= :start')
                ->andWhere('orders.order_date_time <= :end')
                ->setParameters([
                    'start' => $parameters['startDate'],
                    'end' => $parameters['endDate']
                ]);
        }
        if (in_array('gross', $includes)) {
            $query->addSelect("SUM(
                    IF(
                        orders.tax_status = 'gross' OR orders.tax_status = 'tax-free',
                        lineitems.total_price/orders.currency_factor,
                        (((JSON_EXTRACT(lineitems.price,'$.taxRules[0].taxRate')/100)+1)*lineitems.total_price)/orders.currency_factor
                    )
                )
                as sales")
                ->orderBy('sales', 'DESC');
        }
        elseif (in_array('net', $includes)) {
            $query->addSelect("SUM(
                    IF(
                        orders.tax_status = 'net' OR orders.tax_status = 'tax-free',
                        lineitems.total_price/orders.currency_factor,
                        (lineitems.total_price/((JSON_EXTRACT(lineitems.price,'$.taxRules[0].taxRate')/100)+1))/orders.currency_factor
                    )
                )
                as sales")
                ->orderBy('sales', 'DESC');
        } elseif (in_array('gross_net', $includes)) {
            $query->addSelect([
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
            ]);
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

        return $query;
    }

    // $data: payload Spalte aus cart Tabelle, kann komprimiert sein
    public static function detectAndDecompress(string $data): string
    {
        try {
            if (strncmp($data, "\x1f\x8b", 2) === 0) {
                return gzdecode($data); // Gzip-Daten entpacken
            }

            // Prüft, ob die Daten zstd-komprimiert sind
            $zstdMagicBytes = pack('H*', '28B52FFD');
            if (strncmp($data, $zstdMagicBytes, 4) === 0) {
                // Prüfen, ob PECL-Extension verfügbar ist
                if (function_exists('zstd_uncompress')) {
                    return zstd_uncompress($data); // Zstd-Daten mit PECL-Extension entpacken
                }

                // Prüfen, ob die kornrunner/php-zstd Bibliothek verfügbar ist
                if (class_exists('\Zstd\Zstd')) {
                    return \Zstd\Zstd::uncompress($data); // Zstd-Daten mit Composer-Bibliothek entpacken
                }

                // Prüfen, ob die Appoly\ZstdPhp\ZSTD Bibliothek verfügbar ist
                if (class_exists('Appoly\ZstdPhp\ZSTD')) {
                    return \Appoly\ZstdPhp\ZSTD::uncompress($data); // Zstd-Daten mit Appoly-Bibliothek entpacken
                }

                // Prüfen, ob das zstd-CLI-Tool verfügbar ist
                if (is_executable('unzstd')) {
                    $tmpFile = tempnam(sys_get_temp_dir(), 'zstd_');
                    file_put_contents($tmpFile . '.zst', $data);
                    exec("unzstd --stdout {$tmpFile}.zst 2>/dev/null", $output, $returnCode);
                    unlink($tmpFile . '.zst');
                    if ($returnCode === 0) {
                        return implode("\n", $output);
                    }
                }
            }

        } catch (\Exception $e) {

        }

        return $data;
    }
}
