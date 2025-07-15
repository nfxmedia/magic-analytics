<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;

use Cbax\ModulAnalytics\Components\Base;

class SalesByCountry implements StatisticsInterface
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

        if (empty($parameters['blacklistedStatesIds']['delivery']))
        {
            $criteria->addAssociation('deliveries');
            $filters[] = new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new ContainsFilter('deliveries.shippingCosts', '-')
                ]
            );
        }

        $criteria->addAssociation('order.deliveries.shippingOrderAddress');
        $criteria->addAggregation(new EntityAggregation('countries', 'deliveries.shippingOrderAddress.countryId', 'country'));

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross')
        {
            $amount = 'amountTotal';
        } else {
            $amount = 'amountNet';
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-sales-by-country',
                new TermsAggregation(
                    'sales-by-country',
                    'order.deliveries.shippingOrderAddress.countryId',
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

        $sortedData = $this->base->getDataFromAggregations($result,'sales','sales-by-country','countries');
        $seriesData = StatisticsHelper::limitData($sortedData, $parameters['config']['chartLimit']);
        $gridData   = StatisticsHelper::limitData($sortedData, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($sortedData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData, 'seriesData' => $seriesData];
    }
}






