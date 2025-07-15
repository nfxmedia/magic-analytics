<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

use Cbax\ModulAnalytics\Components\Base;

class CustomerAge implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $customerRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('active', 1));

        if (!empty($parameters['customerGroupIds'])) {
            $criteria->addFilter(new EqualsAnyFilter('groupId', $parameters['customerGroupIds']));
        }

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_OR,
                [
                    new EqualsFilter('birthday', NULL)
                ]
            )
        );

        if (!empty($parameters['salesChannelIds'])) {
            $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        $criteria->addAggregation(
            new DateHistogramAggregation(
                'customer_age',
                'birthday',
                DateHistogramAggregation::PER_YEAR
            )
        );

        try {
            $result = $this->customerRepository->search($criteria, $context);
            $aggregation = $result->getAggregations()->get('customer_age');

        } catch (\Exception) {
            return ['success' => true, 'seriesData' => []];
        }

        $data = [];
        $seriesdata = [];

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            if (empty($key) || str_starts_with($key, '-') || str_starts_with($key, '0')) continue;
            $year = \DateTime::createFromFormat('Y-m-d', $key)?->format('Y');
            if (!empty($year)) {
                $data[$year] = 0;
            }
        }

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            if (empty($key) || str_starts_with($key, '-') || str_starts_with($key, '0')) continue;
            $year = \DateTime::createFromFormat('Y-m-d', $key)?->format('Y');
            if (!empty($year)) {
                $sum = $bucket->getCount();
                $data[$year] += $sum;
            }
        }

        $total = array_sum($data);
        $thisYear = date("Y");

        foreach ($data as $key => $value) {
            $seriesdata[] = [
                'age' => (string)($thisYear - $key),
                'percent' => round(100 * $value/$total, 1) . ' %',
                'count' => (int)$value
            ];
        }

        $seriesdata = array_reverse($seriesdata);

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($seriesdata, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $seriesdata];
    }
}



