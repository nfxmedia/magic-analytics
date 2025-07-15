<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cbax\ModulAnalytics\Components\Base;

class ProductLowInstock implements StatisticsInterface
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
        $includes = ['options' => true, 'stock' => true, 'active' => true, 'limit' => $limit];
        $sortings = [['field' => 'stock', 'direction' => FieldSorting::ASCENDING]];

        [$data, $parents] = $this->base->getProductsForOverviews($parameters, $modifiedContext, $includes, $sortings);

        if (!empty($parameters['showVariantParent']) && !empty($parents)) {
            foreach ($data as $key => $prod) {
                if (!empty($prod['parentId']) && !empty($parents[$prod['parentId']])) {
                    $parents[$prod['parentId']]['sum'] = ($parents[$prod['parentId']]['sum'] ?? 0) + $prod['sum'];
                    unset($data[$key]);
                }
            }

            $data = array_merge(array_values($data), array_values($parents));
            $data = StatisticsHelper::sortArrayByColumn($data, 'sum', 'ASC');
            $data = StatisticsHelper::limitData($data, $limit);
        }

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $data];
    }
}

