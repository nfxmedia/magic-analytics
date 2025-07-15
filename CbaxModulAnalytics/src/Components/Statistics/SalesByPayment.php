<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByPayment implements StatisticsInterface
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

        // damit Orders mit geänderter Zahlart nicht mehrfach gezählt werden
        $disregardedStates = ['cancelled', 'failed'];

        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        if (empty($parameters['blacklistedStatesIds']['transaction']))
        {
            $criteria->addAssociation('transactions');
            $filters[] = new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsAnyFilter('transactions.stateMachineState.technicalName', $disregardedStates)
                ]
            );
        }

        $criteria->addAggregation(new EntityAggregation('paymentMethods', 'transactions.paymentMethodId', 'payment_method'));

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross')
        {
            $amount = 'amountTotal';
        } else {
            $amount = 'amountNet';
        }

        // FilterAggregation wegen Transactions von Orders mit geänderter Zahlart und doppelten Einträgen in deliveries
        $criteria->addAggregation(
            new FilterAggregation(
                'filter-sales-by-payment',
                new TermsAggregation(
                    'sales-by-payment',
                    'order.transactions.paymentMethodId',
                    null,
                    null,
                    new TermsAggregation(
                        'sales-by-currency',
                        'currencyFactor',
                        null,
                        null,
                        new SumAggregation('sum-order', $amount)
                    )
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $modifiedContext);

        $gridData = $this->base->getDataFromAggregations($result,'sales','sales-by-payment','paymentMethods');

        $seriesData = StatisticsHelper::limitData($gridData, $parameters['config']['chartLimit']);
        //$gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($gridData, $parameters['labels'], $parameters['config'])];
        }

        return ["success" => true, 'gridData' => $gridData, 'seriesData' => $seriesData];
    }
}


