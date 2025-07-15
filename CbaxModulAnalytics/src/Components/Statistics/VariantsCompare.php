<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

use Cbax\ModulAnalytics\Components\Base;

class VariantsCompare implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $propertyGroupOptionRepository,
        private readonly EntityRepository $productRepository,
        private readonly Connection $connection
    ) {

    }

    public function getStatisticsData($parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);
        $overall = [];
        $overall['sales'] = 0;
        $overall['sum'] = 0;
        $overall['count'] = 0;

        $optionCriteria = new Criteria();
        $optionCriteria->addFilter(new EqualsFilter('groupId', $parameters['propertyGroupId']));
        $optionsSearch = $this->propertyGroupOptionRepository->search($optionCriteria, $modifiedContext);
        $optionIds = $optionsSearch->getIds();
        $options = $optionsSearch->getElements();

        $productCriteria = new Criteria();
        $productCriteria->setOffset(0)
            ->setLimit(500);
        $productCriteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_OR,
            [
                new EqualsFilter('parentId', null)
            ]
        ));
        $productCriteria->addAssociation('options');
        $productCriteria->getAssociation('options')
            ->addSorting(new FieldSorting('groupId'))
            ->addSorting(new FieldSorting('id'));
        $productCriteria->addFilter(new EqualsFilter('options.groupId', $parameters['propertyGroupId']));

        if (!empty($parameters['categoryId']))
        {
            $productCriteria->addAssociation('categoriesRo');
            $productCriteria->addFilter(new EqualsFilter('product.categoriesRo.id', $parameters['categoryId']));
        }

        $productIterator = new RepositoryIterator($this->productRepository, $modifiedContext, $productCriteria);
        $products = [];
        while ($searchResult = $productIterator->fetch()) {
            /** @var ProductEntity $product */
            foreach ($searchResult as $product) {
                $products[$product->getId()] = $product->getOptionIds();
            }
        }

        if (empty($products)) {
            return ['success' => true, 'gridData' => [], 'seriesData' => [], 'overall' => $overall];
        }

        $productIdsBytes = UUid::fromHexToBytesList(array_keys($products));

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'SUM(lineitems.quantity) as `sum`',
                'products.option_ids as optionIds'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->andWhere('lineitems.product_id IN (:modProductIds)')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('lineitems.quantity > 0')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
            ])
            ->setParameter('modProductIds', $productIdsBytes, ArrayParameterType::STRING)
            ->groupBy('optionIds');

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

        $result = $query->fetchAllAssociative();

        $data = [];
        foreach($optionIds as $optionId) {
            $counter2 = 0;
            foreach ($products as $productOptionIds)
            {
                if (in_array($optionId, $productOptionIds)) {
                    $counter2++;
                }
            }
            if ($counter2 === 0) continue;

            $dataSet = [
                'name' => $options[$optionId]->getTranslated()['name'],
                'sum' => 0,
                'sales' => 0,
                'count' => $counter2
            ];
            foreach ($result as $item) {
                if (str_contains($item['optionIds'], $optionId)) {
                    $dataSet['sum'] += (int)$item['sum'];
                    $dataSet['sales'] += round((float)$item['sales'], 2);
                }
            }
            $data[] = $dataSet;
        }

        $overall['sales'] = array_sum(array_column($data, 'sales'));
        $overall['sum'] = array_sum(array_column($data, 'sum'));
        $overall['count'] = array_sum(array_column($data, 'count'));

        $data = StatisticsHelper::sortArrayByColumn($data, 'sales');
        $seriesData = StatisticsHelper::limitData($data, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($data, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData, 'overall' => $overall];
    }
}



