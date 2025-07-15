<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class ProductsProfit implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        $includes = ['options' => true, 'price' => true, 'purchasePrice' => true];
        [$data, $parents] = $this->base->getProductsForOverviews($parameters, $modifiedContext, $includes);

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        $criteria->addAssociation('lineItems');
        $criteria->getAssociation('lineItems')->addAssociation('product');

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-count-by-product',
                new TermsAggregation(
                    'count-by-product',
                    'order.lineItems.product.id',
                    null,
                    null,
                    new SumAggregation('sum-order', 'order.lineItems.quantity')
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $modifiedContext);
        $aggregation = $result->getAggregations()->get('count-by-product');

        $productSaleData = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            if (empty($bucket->getKey())) continue;
            $productSaleData[$bucket->getKey()] = (int)$bucket->getResult()->getSum();
        }

        if (!empty($parameters['showVariantParent']) && !empty($parents)) {
            foreach ($data as $key => $prod) {
                if (!empty($prod['parentId']) && !empty($parents[$prod['parentId']])) {
                    $parents[$prod['parentId']]['profit'] = ($parents[$prod['parentId']]['profit'] ?? 0) + round(($prod['cprice'] - $prod['pprice']) * (float)($productSaleData[$prod['id']] ?? 0), 2);
                    $parents[$prod['parentId']]['sum'] = ($parents[$prod['parentId']]['sum'] ?? 0) + ($productSaleData[$prod['id']] ?? 0);
                    $parents[$prod['parentId']]['pprice'] = 'NA';
                    $parents[$prod['parentId']]['cprice'] = 'NA';
                    $parents[$prod['parentId']]['markUp'] = 'NA';
                    unset($data[$key]);
                }
            }
        }

        foreach ($data as &$prod) {
            $prod['sum'] = $productSaleData[$prod['id']] ?? 0;
            $prod['profit'] = round(($prod['cprice'] - $prod['pprice']) * (float)$prod['sum'], 2);
            $prod['markUp'] = !empty($prod['cprice']) ?
                round(100 * ($prod['cprice'] - $prod['pprice']) / $prod['cprice'], 1) : null;
        }

        $data = array_merge(array_values($data), array_values($parents));

        $overall = array_sum(array_column($data, 'profit'));
        $sortingField = $parameters['sortBy'] ?? 'sales';
        $direction = $parameters['sortDirection'] ?? 'DESC';

        $data = StatisticsHelper::sortArrayByColumn($data, $sortingField, $direction);

        if ($parameters['format'] === 'csv') {
            $sortedColumnsdata = [];
            foreach ($data as $item) {
                $sortedColumnsdata[] = [
                    'id' => $item['id'] ?? '',
                    'number' => $item['number'] ?? '',
                    'name' => $item['name'] ?? '',
                    'profit' => $item['profit'] ?? '',
                    'markUp' => ($item['markUp'] ?? '') . '%',
                    'sum' => $item['sum'] ?? '',
                    'pprice' => $item['pprice'] ?? '',
                    'cprice' => $item['cprice'] ?? ''
                ];
            }
            return ['success' => true, 'fileSize' => $this->base->exportCSV($sortedColumnsdata, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'overall' => $overall, 'gridData' => $data];
    }
}

