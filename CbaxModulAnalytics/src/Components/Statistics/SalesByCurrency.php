<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByCurrency implements StatisticsInterface
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

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        $criteria->addAggregation(new EntityAggregation('currencies', 'currencyId', 'currency'));

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross') {
            $totalColumn = 'amountTotal';
        } else {
            $totalColumn = 'amountNet';
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-sales-by-currency',
                new TermsAggregation(
                    'sales-by-currency',
                    'currencyId',
                    null,
                    null,
                    new SumAggregation('sum-order', $totalColumn)
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $modifiedContext);

        $aggregation = $result->getAggregations()->get('sales-by-currency');
        $entityElements = $result->getAggregations()->get('currencies')->getEntities()->getElements();

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket)
        {
            $name = !empty($entityElements[$bucket->getKey()]) ? $entityElements[$bucket->getKey()]->getTranslated()['name'] : '';

            if (!empty($name))
            {
                $entry = [
                    'name' => $name,
                    'count' => (int)$bucket->getCount(),
                    'sum' => round((float)$bucket->getResult()->getSum(), 2)
                ];
                if ($parameters['format'] !== 'csv') {
                    $entry['shortName'] = $entityElements[$bucket->getKey()]->getShortName() ?? null;
                    $entry['symbol'] = $entityElements[$bucket->getKey()]->getSymbol() ?? null;
                }
                $data[] = $entry;
            }
        }

        $sortedData =  StatisticsHelper::sortArrayByColumn($data);

        $seriesData = StatisticsHelper::limitData($sortedData, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($sortedData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData];
    }

}


