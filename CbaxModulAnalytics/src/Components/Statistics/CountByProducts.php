<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Defaults;

use Cbax\ModulAnalytics\Components\Base;

class CountByProducts implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $productRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');

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

        $result = $this->orderRepository->search($criteria, $context);

        $aggregation = $result->getAggregations()->get('count-by-product');

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            if (empty($key)) continue;
            $parentID = null;
            $productNumber = null;

            $productCriteria = new Criteria();
            $productCriteria->addFilter(new EqualsFilter('id', $key));
            $productCriteria->addAssociation('translations');
            $productSearch = $this->productRepository->search($productCriteria, $context)->first();
            if (!empty($productSearch)) {

                $productNumber = $productSearch->getProductNumber();
                $parentID = $productSearch->getParentId();
                $translation = $productSearch->getTranslations()->filterByLanguageId($languageId)->first();

                if (empty($translation) && Defaults::LANGUAGE_SYSTEM != $languageId) {
                    $translation = $productSearch->getTranslations()->filterByLanguageId(Defaults::LANGUAGE_SYSTEM)->first();
                }

                if (!empty($translation) && empty($translation->getName()) && Defaults::LANGUAGE_SYSTEM != $languageId) {
                    $translation = $productSearch->getTranslations()->filterByLanguageId(Defaults::LANGUAGE_SYSTEM)->first();
                }

                if ((empty($translation) && !empty($parentID)) || (!empty($translation) && empty($translation->getName()) && !empty($parentID))) {
                    $productCriteria = new Criteria();
                    $productCriteria->addFilter(new EqualsFilter('id', $parentID));
                    $productCriteria->addAssociation('translations');
                    $mainVariantSearch = $this->productRepository->search($productCriteria, $context)->first();

                    if (!empty($mainVariantSearch)) {
                        $translation = $mainVariantSearch->getTranslations()->filterByLanguageId($languageId)->first();
                    }

                    if (!empty($mainVariantSearch) && empty($translation) && Defaults::LANGUAGE_SYSTEM != $languageId) {
                        $translation = $mainVariantSearch->getTranslations()->filterByLanguageId(Defaults::LANGUAGE_SYSTEM)->first();
                    }
                }

                if (!empty($translation)) {
                    $data[] = [
                        'id' => $key,
                        'number' => $productNumber,
                        'name' => $translation->getName(),
                        'sum' => (int)$bucket->getResult()->getSum()
                    ];
                }
            }
        }

        $sortedData = StatisticsHelper::sortArrayByColumn($data);
        $seriesData = StatisticsHelper::limitData($sortedData, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($sortedData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData];
    }
}






