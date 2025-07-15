<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SearchActivity implements StatisticsInterface
{
   public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $searchRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $parameters['blacklistedStatesIds'] = [];
        $criteria = StatisticsHelper::getBaseCriteria('createdAt', $parameters, false);

        $criteria->addAggregation(
            new EntityAggregation('salesChannels', 'salesChannelId', 'sales_channel')
        );

        $criteria->addAggregation(
            new DateHistogramAggregation(
                'search-sum-day',
                'createdAt',
                DateHistogramAggregation::PER_DAY
            )
        );

        $result = $this->searchRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('search-sum-day');

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket)
        {
            $data[] = [
                'date' => explode(' ', $bucket->getKey())[0],
                'formatedDate' => StatisticsHelper::getFormatedDate($bucket->getKey(), $parameters['adminLocalLanguage']),
                'count' => (int)round((float)$bucket->getCount(),0)
            ];
        }

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $data];
    }
}
