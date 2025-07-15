<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

use Cbax\ModulAnalytics\Components\Base;

class SingleProduct implements StatisticsInterface
{
   public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $productImpressionRepository,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $productRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        $productId = $parameters['productId'];
        if (empty($productId)) return [
            'success' => true,
            'seriesData' => [],
            'productName' => [],
            'seriesComparedata' => [],
            'compareProductNames' => [],
            'gridData' => []
        ];

        $compareIds = is_array($parameters['compareIds']) ? $parameters['compareIds'] : [];
        [$productName, $idsArray] = $this->getProductData($productId, $parameters, $modifiedContext);

        $seriesData = $this->getSeriesdata($idsArray, $parameters, $context);
        $gridData = array_filter($seriesData, function($value) {
            return $value['count'] > 0 || $value['clicks'] > 0;
        });
        $gridData = array_reverse($gridData);

        $seriesCompareData = [];
        $compareProductNames = [];

        foreach ($compareIds as $prodId)
        {
            [$compareProductNames[], $idsArray] = $this->getProductData($prodId, $parameters, $modifiedContext);
            $seriesCompareData[] = $this->getSeriesdata($idsArray, $parameters, $context);
        }

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($gridData, $parameters['labels'], $parameters['config'])];
        }

        return [
            'success' => true,
            'seriesData' => $seriesData,
            'productName' => $productName,
            'seriesCompareData' => $seriesCompareData,
            'compareProductNames' => $compareProductNames,
            'gridData' => $gridData
        ];
    }

    private function getProductData(string $productId, array $parameters, Context $context): array
    {
        $context->setConsiderInheritance(true);
        $criteria = new Criteria([$productId]);
        $criteria->setLimit(1);
        $criteria->addAssociation('options');
        $criteria->getAssociation('options')
            ->addSorting(new FieldSorting('groupId'))
            ->addSorting(new FieldSorting('id'));
        $prod = $this->productRepository->search($criteria, $context)->first();

        $name = '';
        $optionNames = '';
        if (!empty($prod))
        {
            $name = $prod->getName() ?? $prod->getTranslated()['name'];
            if (!empty($prod->getParentId())) {
                $options = $prod->getOptions()->getElements();
                foreach($options as $option) {
                    $optionName = $option->getTranslated()['name'];
                    if (!empty($optionName)) {
                        $optionNames .= ' ' . $optionName;
                    }
                }
            }
        }

        if (empty($parameters['showVariantParent']) || (empty($prod->getParentId()) && $prod->getChildCount() === 0)) {
            return [$name . $optionNames, [$productId]];

        } else {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('parentId', $productId));
            $allVariantIds = $this->productRepository->searchIds($criteria, $context)->getIds();

            return [$name . $optionNames, $allVariantIds];
        }
    }

    private function getSeriesdata(array $productIds, array $parameters, Context $context): array
    {
        $range = StatisticsHelper::getDatesFromRange($parameters['startDate'], $parameters['endDate']);

        $criteria = StatisticsHelper::getBaseCriteria('date', $parameters, false);
        $criteria->setLimit(1000);
        $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));
        $criteria->addSorting(new FieldSorting('date', FieldSorting::ASCENDING));

        $data = [];
        $result = $this->productImpressionRepository->search($criteria, $context)->getElements();
        if (!empty($result)) {
            foreach ($result as $item) {
                $date = $item->getDate()?->format('Y-m-d');
                if (empty($date)) {
                    continue;
                }
                if (empty($data[$date])) {
                    $data[$date] = [
                        'date' => $item->getDate()?->format('Y-m-d'),
                        'formatedDate' => StatisticsHelper::getFormatedDate($item->getDate(), $parameters['adminLocalLanguage']),
                        'count' => 0,
                        'clicks' => (int)$item->getImpressions(),
                    ];
                } else {
                    $data[$date]['clicks'] += (int)$item->getImpressions();
                }
            }
        }

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);
        $criteria->addAssociation('lineItems');
        $criteria->addFilter(new EqualsAnyFilter('lineItems.productId', $productIds));

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-product-sales-day',
                new DateHistogramAggregation(
                    'product-sales-day',
                    'orderDate',
                    DateHistogramAggregation::PER_DAY,
                    null,
                    new SumAggregation('sum-order', 'lineItems.quantity')
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('product-sales-day');

        foreach ($aggregation->getBuckets() as $bucket)
        {
            $day = explode(' ', $bucket->getKey())[0];
            if (!isset($data[$day]))
            {
                $data[$day] = [
                    'date' => $day,
                    'formatedDate' => StatisticsHelper::getFormatedDate($bucket->getKey(), $parameters['adminLocalLanguage']),
                    'count' => (int)$bucket->getResult()->getSum(),
                    'clicks' => 0
                ];
            } else {
                $data[$day]['count'] = (int)$bucket->getResult()->getSum();
            }
        }

        foreach ($range as $day)
        {
            if (!isset($data[$day]))
            {
                $data[$day] = [
                    'date' => $day,
                    'formatedDate' => StatisticsHelper::getFormatedDate($day, $parameters['adminLocalLanguage']),
                    'count' => 0,
                    'clicks' => 0
                ];
            }
        }
        ksort($data);

        return array_values($data);
    }

}






