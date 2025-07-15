<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cbax\ModulAnalytics\Components\Base;

class ProductByOrders implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $context->setConsiderInheritance(true);
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        $criteria->addAssociation('lineItems');

        if (!empty($parameters['productSearchIds'])) {
            $filters[] = new EqualsAnyFilter('order.lineItems.productId', $parameters['productSearchIds']);
        }

        if (!empty($parameters['manufacturerSearchIds'])) {
            $criteria->addAssociation('lineItems.product');
            $criteria->addFilter(new EqualsAnyFilter('order.lineItems.product.manufacturerId', $parameters['manufacturerSearchIds']));
        }

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

        //product Daten Name, ProductNumber holen
        [$products, $parents] = $this->base->getProductsBasicData($parameters, $modifiedContext, $aggregation->getKeys());

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            if (empty($key) || empty($products[$key])) continue;

            if (empty($parameters['showVariantParent']) || empty($products[$key]['parentId'])) {
                $data[] = [
                    'id' => $key,
                    'number' => $products[$key]['number'],
                    'name' => $products[$key]['name'],
                    'sum' => (int)$bucket->getCount()
                ];
            } elseif (!empty($parents[$products[$key]['parentId']])) {
                $parents[$products[$key]['parentId']]['sum'] = ($parents[$products[$key]['parentId']]['sum'] ?? 0) + (int)$bucket->getCount();
            }
        }

        $data = array_merge(array_values($data), array_values($parents));
        $sortedData = StatisticsHelper::sortArrayByColumn($data);
        $seriesData = StatisticsHelper::limitData($sortedData, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($sortedData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData];
    }
}


