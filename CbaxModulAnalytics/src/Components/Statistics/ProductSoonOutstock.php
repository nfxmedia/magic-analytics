<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class ProductSoonOutstock implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $canceledId = $this->base->getCanceledStateId($context);
        $parameters['showVariantParent'] = false;

        $criteria = new Criteria();
        $criteria->setLimit(1);

        $criteria->addFilter(
            new RangeFilter('orderDate', [
                RangeFilter::GTE => date('Y-m-d', mktime(0, 0, 0, (int)date("m"), (int)date("d") - $parameters['config']['lookBackDays'], (int)date("Y")))
            ])
        );

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('stateId', $canceledId)
                ]
            )
        );

        if (!empty($parameters['salesChannelIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $filters = [];
        $filters[] = new EqualsAnyFilter('order.lineItems.product.active', [1, true]);
        $filters[] = new RangeFilter('lineItems.product.stock', [
            RangeFilter::GT => 0
        ]);
        if (!empty($parameters['productSearchIds'])) {
            $filters[] = new EqualsAnyFilter('order.lineItems.product.id', $parameters['productSearchIds']);
        }

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

        $context->setConsiderInheritance(true);
        $result = $this->orderRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('count-by-product');
        $context->setConsiderInheritance(false);
        //product Daten Name, ProductNumber holen
        [$products, $parents] = $this->base->getProductsBasicData($parameters, $context, $aggregation->getKeys());
        $data = [];

        foreach ($aggregation->getBuckets() as $bucket)
        {
            $key = $bucket->getKey();
            if (empty($key) || empty($products[$key])) continue;

            $data[] = [
                'id' => $key,
                'number' => $products[$key]['number'],
                'name' => $products[$key]['name'],
                'sum' => (int)$this->getDaysStockLasting((int)$bucket->getResult()->getSum(), $products[$key]['sum'], $parameters['config']['lookBackDays']),
                'stock' => $products[$key]['sum'],
                'sold' => (int)$bucket->getResult()->getSum()
            ];
        }

        $sortedData = StatisticsHelper::sortArrayByColumn($data, 'sum', 'ASC');
        $gridData = array_slice($sortedData, 0, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($sortedData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData];
    }

    private function getDaysStockLasting($sold, $stock, $lookBackDays)
    {
        return round($stock / ($sold / $lookBackDays));
    }
}


