<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

use Cbax\ModulAnalytics\Components\Base;

class NewCustomersByTime implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $customerRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $criteria = StatisticsHelper::getBaseCriteria('createdAt', $parameters, false);

        if (!empty($parameters['customerGroupIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('groupId', $parameters['customerGroupIds']));
        }

        $criteria->addAggregation(
            new DateHistogramAggregation(
                'new_customer_count_month',
                'createdAt',
                DateHistogramAggregation::PER_MONTH
            )
        );

        $customerFilters = [
            new RangeFilter('orderCount', [
                RangeFilter::GT => 0
            ])
        ];
        $criteria->addAggregation(
            new FilterAggregation(
                'filter-new-paying-customer-count-month',
                new DateHistogramAggregation(
                    'new-paying-customer-count-month',
                    'createdAt',
                    DateHistogramAggregation::PER_MONTH
                ),
                $customerFilters
            )
        );

        $result = $this->customerRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('new_customer_count_month');
        $aggregation2 = $result->getAggregations()->get('new-paying-customer-count-month');

        $data = [];
        $seriesdata = [];

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $data[$month] = 0;
            $data2[$month] = 0;
        }

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $sum = $bucket->getCount();
            $data[$month] += $sum;
        }

        foreach ($aggregation2->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $month = \DateTime::createFromFormat('Y-m-d', $key)?->format('m/Y');
            $sum = $bucket->getCount();
            $data2[$month] += $sum;
        }

        foreach ($data as $key => $value) {
            $seriesdata[] = [
                'date' => $key,
                'sum' => (int)$value,
                'paying' => !empty($data2[$key]) ? (int)$data2[$key] : 0
            ];
        }

        $seriesdata = array_reverse($seriesdata);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($seriesdata, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $seriesdata];
    }
}

