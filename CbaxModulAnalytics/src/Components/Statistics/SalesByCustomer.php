<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SalesByCustomer implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        $criteria->addAssociation('orderCustomer');
        $criteria->addAggregation(new EntityAggregation('customers', 'orderCustomer.customerId', 'customer'));

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross') {
            $totalColumn = 'amountTotal';
        } else {
            $totalColumn = 'amountNet';
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-sales-by-customer',
                new TermsAggregation(
                    'sales-by-customer',
                    'order.orderCustomer.customerId',
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

        $result = $this->orderRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('sales-by-customer');
        $customers = $result->getAggregations()->get('customers')->getEntities()->getElements();

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket)
        {
            $key = $bucket->getKey();
            if (empty($key)) continue;
            if (empty($customers[$key])) continue;
            $sum = StatisticsHelper::calculateAmountInSystemCurrency($bucket->getResult());

            $data[] = [
                'id' => $key,
                'number' => $customers[$key]->getCustomerNumber(),
                'name' => $customers[$key]->getFirstName() . ' ' . $customers[$key]->getLastName(),
                'count' => (int)$bucket->getCount(),
                'sum' => round($sum, 2),
                'email' => $customers[$key]->getEmail(),
                'lastLogin' => $customers[$key]->getLastLogin() ? $customers[$key]->getLastLogin()->format('Y-m-d H:i:s') : ''
            ];
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


