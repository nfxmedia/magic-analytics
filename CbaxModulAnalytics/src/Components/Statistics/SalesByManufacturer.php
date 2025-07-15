<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Cbax\ModulAnalytics\Components\Base;

class SalesByManufacturer implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $productManufacturerRepository,
        private readonly Connection $connection
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);
        $sortingField = $parameters['sortBy'] ?? 'sum';
        $direction = $parameters['sortDirection'] ?? 'DESC';

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'IF(products.product_manufacturer_id IS NOT NULL, products.product_manufacturer_id, parents.product_manufacturer_id)  as `id`',
                'SUM(lineitems.quantity) as `count`'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->leftJoin('products', '`product`', 'parents', 'products.parent_id = parents.id')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('products.parent_version_id = :versionId')
            ->andWhere('IF(parents.version_id IS NOT NULL, parents.version_id = :versionId, 1)')
            ->andWhere('IF(products.product_manufacturer_version_id IS NOT NULL, products.product_manufacturer_version_id = :versionId, 1)')
            ->andWhere('IF(products.product_manufacturer_id IS NOT NULL, products.product_manufacturer_id, parents.product_manufacturer_id) IS NOT NULL')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('lineitems.quantity > 0')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                'language1' => $languageId,
                'language2' => Defaults::LANGUAGE_SYSTEM
            ])
            ->groupBy('`id`');

        if ($sortingField === 'sum' || $sortingField === 'count') {
            $query->orderBy($sortingField, $direction);
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
                as `sum`"
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
                as `sum`"
            ]);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $manufacturers = $query->fetchAllAssociative();
        $ids = [];

        foreach($manufacturers as &$manufacturer)
        {
            $manufacturer['count'] = (int)$manufacturer['count'];
            $manufacturer['sum'] = round((float)$manufacturer['sum'], 2);
            $manufacturer['id'] = Uuid::fromBytesToHex($manufacturer['id']);
            $ids[] = $manufacturer['id'];
        }
        unset($manufacturer);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $ids));
        $result = $this->productManufacturerRepository->search($criteria, $modifiedContext)->getElements();

        $data = [];
        foreach($manufacturers as $manufacturer)
        {
            $data[] = [
                'id' => $manufacturer['id'],
                'name' => !empty($result[$manufacturer['id']]) ? $result[$manufacturer['id']]->getTranslated()['name'] : '',
                'count' => $manufacturer['count'],
                'sum' => $manufacturer['sum']
            ];
        }

        if ($sortingField === 'name') {
            $data = StatisticsHelper::sortArrayByColumn($data, $sortingField, $direction);
        }
        $seriesData = StatisticsHelper::limitData($data, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($data, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData];
    }
}




