<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByLanguage implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $languageRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);
        $criteria->addAggregation(new EntityAggregation('languages', 'languageId', 'language'));

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross') {
            $totalColumn = 'amountTotal';
        } else {
            $totalColumn = 'amountNet';
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-sales-by-language',
                new TermsAggregation(
                    'sales-by-language',
                    'languageId',
                    null,
                    null,
                    new TermsAggregation(
                        'sales-by-currency',
                        'currencyFactor',
                        null,
                        null,
                        new SumAggregation('sum-order', $totalColumn)
                    )
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $modifiedContext);

        $aggregation = $result->getAggregations()->get('sales-by-language');
        $languageIds = $result->getAggregations()->get('languages')->getEntities()->getKeys();
        $data = [];
        $languageResult = [];

        if (!empty($languageIds))
        {
            $languageCriteria = new Criteria();
            $languageCriteria->addFilter(new EqualsAnyFilter('id', $languageIds));
            $languageCriteria->addAssociation('locale');

            $languageResult = $this->languageRepository->search($languageCriteria, $modifiedContext)->getElements();
        }

        foreach ($aggregation->getBuckets() as $bucket)
        {
            $key = $bucket->getKey();
            if (empty($key)) continue;
            if (empty($languageResult[$key])) continue;
            $name = $languageResult[$key]->getLocale()->getTranslated()['name'];
            if (!empty($name))
            {
                $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());
                $data[] = [
                    'name' => $name,
                    'count' => (int)$bucket->getCount(),
                    'sum' => round($sum, 2)
                ];
            }
        }

        $gridData = StatisticsHelper::sortArrayByColumn($data);
        $seriesData = StatisticsHelper::limitData($gridData, $parameters['config']['chartLimit']);
        //$gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($gridData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData];
    }
}



