<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ArrayParameterType;

use Cbax\ModulAnalytics\Components\Base;

class ProductStream
{
   public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $productRepository,
        private readonly Connection $connection,
        private readonly EntityRepository $productStreamRepository,
        private readonly ProductStreamBuilder $productStreamBuilder
    ) {

    }

    //diese statistik nun unter SalesByProductFilter
    //Platzhalter für später für Stream Vergleich Statistik
    public function getProductStream(array $parameters, Context $context)
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);

        $criteriaProductStream = new Criteria();
        $criteriaProductStream->addFilter(new EqualsFilter('id', $parameters['productStreamId']));

        $result = $this->productStreamRepository->search($criteriaProductStream, $context)->first();

        if (empty($result)) return ['gridData' => [], 'seriesData' => []];

        $productCriteria = new Criteria();
        $filters = $this->productStreamBuilder->buildFilters($parameters['productStreamId'], $context);
        $productCriteria->addFilter(...$filters);
        $productsSearch = $this->productRepository->search($productCriteria, $context);
        //$products = $productsSearch->getElements();
        $productIds = $productsSearch->getIds();
        $parentsIds = [];
        $variants = [];

        foreach ($productsSearch as $prod)
        {
            if ($prod->getChildCount() > 0)
            {
                $parentsIds[] = $prod->getId();
            }
        }
        if (count($parentsIds) > 0)
        {
            $varCriteria = new Criteria();
            $varCriteria->addFilter(new EqualsAnyFilter('parentId', $parentsIds));
            $varCriteria->addAssociation('options');
            $varCriteria->getAssociation('options')
                ->addSorting(new FieldSorting('groupId'))
                ->addSorting(new FieldSorting('id'));
            $varCriteria->addAssociation('options.translations');
            $variantsSearch = $this->productRepository->search($varCriteria, $context);
            $variants = $variantsSearch->getElements();
            //$products = array_merge($products, $variantsSearch->getElements());
            $productIds = array_unique(array_merge($productIds, $variantsSearch->getIds()));
        }
        if (empty($productIds)) return ['gridData' => [], 'seriesData' => []];

        $modProductIds = [];
        foreach ($productIds as $productId)
        {
            $modProductIds[] = Uuid::fromHexToBytes($productId);
        }

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'lineitems.product_id as `id`',
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
            ->setParameter('modProductIds', $modProductIds, ArrayParameterType::STRING)
            ->groupBy('`id`')
            ->orderBy('sales', 'DESC');

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

        $data = $query->fetchAllAssociative();

        foreach($data as &$product)
        {
            $product['sum'] = (int)$product['sum'];
            $product['sales'] = round((float)$product['sales'], 2);
            $product['id'] = Uuid::fromBytesToHex($product['id']);
            if (!empty($product['optionIds']))
            {
                if (!empty($variants[$product['id']])) {
                    $variantOptionNames = $this->base->getVariantOptionsNames($variants[$product['id']]);
                    $product['name'] .= ' - ' . $variantOptionNames;
                }
            }
            unset($product['optionIds']);
        }
        unset($product);

        $overall = array_sum(array_column($data, 'sales'));

        $sortingField = !empty($parameters['sorting'][0]) ? $parameters['sorting'][0] : 'sales';
        $direction = !empty($parameters['sorting'][1]) ? $parameters['sorting'][1] : 'DESC';
        $data = StatisticsHelper::sortArrayByColumn($data, $sortingField, $direction);

        $seriesData = StatisticsHelper::limitData($data, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($data, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return $this->base->exportCSV($data, $parameters['labels'], $parameters['config']);
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData, 'overall' => $overall];
    }
}

