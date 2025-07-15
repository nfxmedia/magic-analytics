<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;

use Cbax\ModulAnalytics\Components\Base;

class CustomerBySalutation implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $customerRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        $criteria = new Criteria();
        $criteria->setLimit(1);

        $criteria->addFilter(new EqualsFilter('active', 1));

        if (!empty($parameters['customerGroupIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('groupId', $parameters['customerGroupIds']));
        }

        if (!empty($parameters['salesChannelIds']))
        {
            $criteria->addFilter(new EqualsAnyFilter('salesChannelId', $parameters['salesChannelIds']));
        }

        $criteria->addAggregation(new EntityAggregation('salutations', 'salutationId', 'salutation'));

        $criteria->addAggregation(
            new TermsAggregation(
                'customer_by_salutation',
                'salutationId'
            )
        );

        $result = $this->customerRepository->search($criteria, $modifiedContext);
        $aggregation = $result->getAggregations()->get('customer_by_salutation');
        $salutations = $result->getAggregations()->get('salutations')->getEntities()->getElements();

        $data = [];

        foreach ($aggregation->getBuckets() as $bucket) {
            $name = $salutations[$bucket->getKey()]->getTranslated()['displayName'] ?? 'Undefined';
            if (empty($name)) $name = 'Undefined';
            $data[] = [
                'name' => $name,
                'count' => (int)$bucket->getCount()
            ];
        }

        $sum = array_sum(array_column($data, 'count'));
        $data = StatisticsHelper::sortArrayByColumn($data, 'count');
        foreach ($data as &$item) {
            $item['percent'] = round(100 * $item['count']/$sum, 1) . ' %';
        }

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $data];
    }
}

