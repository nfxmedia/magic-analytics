<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cbax\ModulAnalytics\Components\Base;

class ProductInactiveWithInstock implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        $limit = 2*$parameters['config']['gridLimit'];
        $includes = ['options' => true, 'stock' => true, 'inactive' => true, 'limit' => $limit];
        $sortings = [['field' => 'stock', 'direction' => FieldSorting::DESCENDING]];

        $data = $this->base->getProductsForOverviews($parameters, $modifiedContext, $includes, $sortings)[0] ?? [];

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $data];
    }
}

