<?php declare(strict_types = 1);

namespace Nfx\MagicAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

use Nfx\MagicAnalytics\Components\ConfigReaderHelper;
use Nfx\MagicAnalytics\Components\Base;

class SearchTrends implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $searchRepository
    ) {

    }

    //for later
    public function getStatisticsData(array $parameters, Context $context): array
    {
        $parameters['blacklistedStatesIds'] = [];
        $criteria = StatisticsHelper::getBaseCriteria('createdAt', $parameters, false);

        return [];
    }
}
