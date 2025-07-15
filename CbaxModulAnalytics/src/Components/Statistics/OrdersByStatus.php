<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class OrdersByStatus implements StatisticsInterface
{
   public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $parameters['blacklistedStatesIds']['order'] = [];

        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        $criteria->addAggregation(new EntityAggregation('orderStates', 'stateId', 'state_machine_state'));

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross') {
            $totalColumn = 'amountTotal';
        } else {
            $totalColumn = 'amountNet';
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-orders-by-status',
                new TermsAggregation(
                    'orders-by-status',
                    'order.stateId',
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

        $sortedData = $this->base->getDataFromAggregations($result,'count','orders-by-status','orderStates');
        $seriesData = StatisticsHelper::limitData($sortedData, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($sortedData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData];
    }
}


