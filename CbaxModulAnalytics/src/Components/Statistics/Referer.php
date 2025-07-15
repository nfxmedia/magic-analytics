<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

use Cbax\ModulAnalytics\Components\Base;

class Referer implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $refererRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $criteria = StatisticsHelper::getBaseCriteria('date', $parameters, false);

        $criteria->addAggregation(
            new TermsAggregation(
                'count-by-referer',
                'referer',
                null,
                null,
            )
        );

        $referersResult = $this->refererRepository->search($criteria, $context);
        $aggregation = $referersResult->getAggregations()->get('count-by-referer');

        $data = [];

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = $bucket->getKey();
            $refererCriteria = new Criteria();
            $refererCriteria->addFilter(new EqualsFilter('referer', $key));

            $refererResult = $this->refererRepository->search($refererCriteria, $context)->first();

            $data[] = [
                'date' => $refererResult->getDate()?->format('d.m.Y'),
                'referer' => $key,
                'deviceType' => $refererResult->deviceType,
                'counted' => $refererResult->counted
            ];
        }

        $sortedData = StatisticsHelper::sortArrayByColumn($data, 'date');

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return['success' => true, 'gridData' => $sortedData];
    }
}


