<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByAccountTypes implements StatisticsInterface
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

        $criteria->addAssociation('orderCustomer');
        $criteria->addAssociation('orderCustomer.customer');

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross') {
            $totalColumn = 'amountTotal';
        } else {
            $totalColumn = 'amountNet';
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-sales_by_account_types',
                new TermsAggregation(
                    'sales_by_account_types',
                    'order.orderCustomer.customer.guest',
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
        $sortedData = $this->base->getDataFromAggregations($result,'sales','sales_by_account_types');

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($sortedData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $sortedData];
    }
}

