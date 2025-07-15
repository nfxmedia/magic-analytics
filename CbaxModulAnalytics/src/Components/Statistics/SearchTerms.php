<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;

use Cbax\ModulAnalytics\Components\Base;

class SearchTerms implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $searchRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);
        $parameters['blacklistedStatesIds'] = [];

        $criteria = StatisticsHelper::getBaseCriteria('createdAt', $parameters, false);

        $criteria->addAggregation(
            new TermsAggregation(
                'search-terms',
                'searchTerm',
                null,
                null,
                new TermsAggregation(
                    'sales-channel',
                    'salesChannelId',
                    null,
                    null,
                    new AvgAggregation(
                        'search-results',
                        'results')
                )
            )
        );

        $criteria->addAggregation(
            new EntityAggregation('salesChannels', 'salesChannelId', 'sales_channel')
        );

        $result = $this->searchRepository->search($criteria, $modifiedContext);
        $aggregation = $result->getAggregations()->get('search-terms');
        $salesChannels = $result->getAggregations()->get('salesChannels')->getEntities()->getElements();

        $data = [];

        foreach ($aggregation->getBuckets() as $bucket)
        {
            foreach ($bucket->getResult()->getBuckets() as $nestedBucket)
            {
                $data[] = [
                    'searchTerm' => $bucket->getKey(),
                    'count' => $nestedBucket->getCount(),
                    'results' => (int)$nestedBucket->getResult()->getAvg(),
                    'salesChannelName' => $salesChannels[$nestedBucket->getKey()]->getTranslated()['name']
                ];
            }
        }

        $sortedData = StatisticsHelper::sortArrayByColumn($data, 'count');
        $gridData = array_slice($sortedData, 0, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($sortedData, $parameters['labels'], $parameters['config'])];
        }

        return ["success" => true, "gridData" => $gridData];
    }
}
