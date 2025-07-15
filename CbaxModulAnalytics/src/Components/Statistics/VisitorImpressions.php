<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

use Cbax\ModulAnalytics\Components\Base;

class VisitorImpressions implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $visitorsRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $criteria = StatisticsHelper::getBaseCriteria('date', $parameters, false);

        $criteria->addAggregation(
            new DateHistogramAggregation(
                'page-impressions-by-day',
                'date',
                DateHistogramAggregation::PER_DAY,
                null,
                new SumAggregation('sum-page-impressions', 'pageImpressions')
            )
        );

        $criteria->addAggregation(
            new FilterAggregation(
                'visitors-by-desktop',
                new DateHistogramAggregation(
                    'desktop',
                    'date',
                    DateHistogramAggregation::PER_DAY,
                    null,
                    new SumAggregation('sum-desktop', 'pageImpressions')
                ),
                [
                    new EqualsFilter('deviceType', 'desktop')
                ]
            )
        );

        $criteria->addAggregation(
            new FilterAggregation(
                'visitors-by-mobile',
                new DateHistogramAggregation(
                    'mobile',
                    'date',
                    DateHistogramAggregation::PER_DAY,
                    null,
                    new SumAggregation('sum-mobile', 'pageImpressions')
                ),
                [
                    new EqualsFilter('deviceType', 'mobile')
                ]
            )
        );

        $criteria->addAggregation(
            new FilterAggregation(
                'visitors-by-tablet',
                new DateHistogramAggregation(
                    'tablet',
                    'date',
                    DateHistogramAggregation::PER_DAY,
                    null,
                    new SumAggregation('sum-tablet', 'pageImpressions')
                ),
                [
                    new EqualsFilter('deviceType', 'tablet')
                ]
            )
        );

        $result = $this->visitorsRepository->search($criteria, $context);

        $aggregation = $result->getAggregations()->get('page-impressions-by-day');

        $aggregationDesktop = $result->getAggregations()->get('desktop');

        $aggregationMobile = $result->getAggregations()->get('mobile');

        $aggregationTablet = $result->getAggregations()->get('tablet');

        $data = [];
        $seriesdata = [];

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $day = \DateTime::createFromFormat('Y-m-d', $key)?->format('d.m.Y');
            $data[$day] = ['pageImpressions' => 0, 'desktop' => 0, 'mobile' => 0, 'tablet' => 0];
        }

        foreach ($aggregation->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $day = \DateTime::createFromFormat('Y-m-d', $key)?->format('d.m.Y');
            $sum = (int)$bucket->getResult()->getSum();
            $data[$day]['pageImpressions'] += $sum;
        }

        foreach ($aggregationDesktop->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $day = \DateTime::createFromFormat('Y-m-d', $key)?->format('d.m.Y');
            $sum = (int)$bucket->getResult()->getSum();
            $data[$day]['desktop'] += $sum;
        }

        foreach ($aggregationMobile->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $day = \DateTime::createFromFormat('Y-m-d', $key)?->format('d.m.Y');
            $sum = (int)$bucket->getResult()->getSum();
            $data[$day]['mobile'] += $sum;
        }

        foreach ($aggregationTablet->getBuckets() as $bucket) {
            $key = explode(' ', $bucket->getKey())[0];
            $day = \DateTime::createFromFormat('Y-m-d', $key)?->format('d.m.Y');
            $sum = (int)$bucket->getResult()->getSum();
            $data[$day]['tablet'] += $sum;
        }

        foreach ($data as $key => $value) {
            $seriesdata[] = [
                'date' => $key,
                'desktop' => $value['desktop'],
                'mobile' => $value['mobile'],
                'tablet' => $value['tablet'],
                'pageImpressions' => $value['pageImpressions']
            ];
        }

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($seriesdata, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $seriesdata];
    }
}
