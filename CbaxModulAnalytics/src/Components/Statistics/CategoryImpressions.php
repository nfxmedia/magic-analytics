<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\Context;

use Cbax\ModulAnalytics\Components\Base;

class CategoryImpressions implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $categoryImpressionsRepository,
        private readonly EntityRepository $categoryRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $context = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        //login Besucher
        $criteria = StatisticsHelper::getBaseCriteria('date', $parameters, false);

        if (!empty($parameters['customerGroupIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('customerGroupId', $parameters['customerGroupIds']));
        } else {
            $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('customerGroupId', null)
                ]
            ));
        }

        $criteria->addAggregation(
            new TermsAggregation(
                'count-by-category',
                'categoryId',
                null,
                null,
                new SumAggregation('sum-impressions', 'impressions')
            )
        );

        $result1 = $this->categoryImpressionsRepository->search($criteria, $context);

        $aggregation1 = $result1->getAggregations()->get('count-by-category');
        //////////////////////

        //not login Besucher
        $criteria = StatisticsHelper::getBaseCriteria('date', $parameters, false);
        $criteria->addFilter(new EqualsFilter('customerGroupId', null));

        $criteria->addAggregation(
            new TermsAggregation(
                'count-by-category',
                'categoryId',
                null,
                null,
                new SumAggregation('sum-impressions', 'impressions')
            )
        );

        $result2 = $this->categoryImpressionsRepository->search($criteria, $context);

        $aggregation2 = $result2->getAggregations()->get('count-by-category');
        ///////////////////

        $data = [];
        foreach ($aggregation1->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            if (empty($key)) continue;

            $categoryCriteria = new Criteria();
            $categoryCriteria->addFilter(new EqualsFilter('id', $key));
            $categoryCriteria->addAssociation('translations');
            $categorySearch = $this->categoryRepository->search($categoryCriteria, $context)->first();

            if (!empty($categorySearch)) {
                $data[$key] = [
                    'id' => $key,
                    'name' => $categorySearch->getTranslated()['name'],
                    'sum1' => (int)$bucket->getResult()->getSum(),
                    'sum2' => 0,
                    'sum' => (int)$bucket->getResult()->getSum()
                ];
            }
        }

        foreach ($aggregation2->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            if (empty($key)) continue;

            if (!empty($data[$key])) {
                $data[$key]['sum2'] = (int)$bucket->getResult()->getSum();
                $data[$key]['sum'] = $data[$key]['sum2'] + $data[$key]['sum1'];
                continue;
            }

            $categoryCriteria = new Criteria();
            $categoryCriteria->addFilter(new EqualsFilter('id', $key));
            $categoryCriteria->addAssociation('translations');
            $categorySearch = $this->categoryRepository->search($categoryCriteria, $context)->first();

            if (!empty($categorySearch)) {
                $data[$key] = [
                    'id' => $key,
                    'name' => $categorySearch->getTranslated()['name'],
                    'sum1' => 0,
                    'sum2' => (int)$bucket->getResult()->getSum(),
                    'sum' => (int)$bucket->getResult()->getSum()
                ];
            }
        }

        $data = array_values($data);
        $overall = array_sum(array_column($data, 'sum'));

        $sortedData = StatisticsHelper::sortArrayByColumn($data);
        $seriesData = StatisticsHelper::limitData($sortedData, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData, 'overall' => $overall];
    }
}
