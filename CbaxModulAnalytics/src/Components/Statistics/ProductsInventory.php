<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Cbax\ModulAnalytics\Components\Base;

class ProductsInventory implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);

        $includes = ['options' => true, 'stock' => true, 'purchasePrice' => true];


        [$data, $parents] = $this->base->getProductsForOverviews($parameters, $modifiedContext, $includes);

        if (!empty($parameters['showVariantParent']) && !empty($parents)) {
            foreach ($data as $key => $prod) {
                if (!empty($prod['parentId']) && !empty($parents[$prod['parentId']])) {
                    $parents[$prod['parentId']]['worth'] = ($parents[$prod['parentId']]['worth'] ?? 0) + ($prod['pprice'] * (float)$prod['sum']);
                    $parents[$prod['parentId']]['sum'] = ($parents[$prod['parentId']]['sum'] ?? 0) + $prod['sum'];
                    $parents[$prod['parentId']]['pprice'] = 'NA';
                    unset($data[$key]);
                }
            }
        }

        foreach ($data as &$prod) {
            $prod['worth'] = $prod['pprice'] * (float)$prod['sum'];
        }

        $data = array_merge(array_values($data), array_values($parents));

        $overall = array_sum(array_column($data, 'worth'));
        $sortingField = $parameters['sortBy'] ?? 'sales';
        $direction = $parameters['sortDirection'] ?? 'DESC';
        $data = StatisticsHelper::sortArrayByColumn($data, $sortingField, $direction);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $data, 'overall' => $overall];
    }
}


