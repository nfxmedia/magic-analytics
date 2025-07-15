<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Cbax\ModulAnalytics\Components\Base;

class PickwareReturns implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly Connection $connection,
        private readonly EntityRepository $manufacturerRepository
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        if (!class_exists('Pickware\PickwareErpStarter\ReturnOrder\Model\ReturnOrderEntity')) {
            return ['success' => true, 'gridData' => []];
        }

        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);
        $modifiedContext = StatisticsHelper::getLanguageModifiedContext($context, $languageId);
        $sortingField = $parameters['sortBy'] ?? 'sum';
        $direction = $parameters['sortDirection'] ?? 'DESC';

        $orderIdsBytes = $this->base->getOrderIdBytesFromDateIntervall($parameters, $context);

        if (empty($orderIdsBytes)) {
            return ['success' => true, 'gridData' => []];
        }

        $data = $this->getProductSalesData($parameters, $languageId, $orderIdsBytes);
        $returnData = $this->base->getProductPWReturnDataFromOrderids($parameters, $orderIdsBytes);

        if (empty($data)) {
            return ['success' => true, 'gridData' => []];
        }

        $manufacturerIds = array_filter(array_unique(array_column($data, 'manufacturerId')));

        if (empty($parameters['showVariantParent'])) {
            //$prod['optionIds'] ist hier string '["xxx","yyy"]' falls nicht null, zu array
            foreach ($data as &$prod) {
                if (!empty($prod['optionIds']) && is_string($prod['optionIds'])) {
                    $prod['optionIds'] = json_decode($prod['optionIds']);
                }
            }
            unset($prod);
            $optionNames = $this->base->getOptionsNamesFromProducts($data, $modifiedContext);
        } else {
            $parentIds = array_filter(array_unique(array_column($data, 'parentId')));
            if (!empty($parentIds)) {
                $parentQuery = StatisticsHelper::getParentQuery($this->connection, $parameters, $languageId, $parentIds);
                $parents = $parentQuery->fetchAllAssociativeIndexed();
                $manufacturerIds = array_filter(array_unique(array_merge($manufacturerIds, array_column($parents, 'manufacturerId'))));
                /* Alternativ mit Repositories:
                 $parents = $this->base->getProductsForOverviews($parameters, $context, [], [], $parentIds)[1] ?? [];
                 */
            } else {
                $parents = [];
            }
        }

        if (!empty($manufacturerIds)) {
            $manufacturerCriteria = new Criteria($manufacturerIds);
            $manufacturers = $this->manufacturerRepository->search($manufacturerCriteria, $modifiedContext)->getElements();
        } else {
            $manufacturers = [];
        }

        foreach($data as &$product) {
            $product['sum'] = (int)$product['sum'];
            $product['returnSum'] = !empty($returnData[$product['id']]) ? (int)$returnData[$product['id']] : 0;
            $product['part'] = round((100*$product['returnSum']) / $product['sum'], 2);

            if (!empty($optionNames) && !empty($product['optionIds']) && is_array($product['optionIds'])) {
                $variantOptionNames = '';
                foreach ($product['optionIds'] as $optionId) {
                    if (!empty($optionNames[$optionId])) {
                        $variantOptionNames .= ' ' . $optionNames[$optionId];
                    }
                }
                $product['name'] .= ' -' . $variantOptionNames;
            }
            unset($product['optionIds']);
            if (empty($product['parentId'])) {
                unset($product['parentId']);
            }
            if (!empty($product['manufacturerId'])) {
                $product['manufacturer'] = $manufacturers[$product['manufacturerId']] ? $manufacturers[$product['manufacturerId']]->getTranslated()['name'] : '';
            } else {
                $product['manufacturer'] = '';
            }

            unset($product['manufacturerId']);
        }
        unset($product);

        if (!empty($parameters['showVariantParent'])) {
            //Parent Daten ermitteln
            $parentdata = [];
            foreach ($data as $key => $prod) {
                if (!empty($prod['parentId']) && !empty($parents[$prod['parentId']])) {
                    if (empty($parentdata[$prod['parentId']])) {
                        if (empty($prod['sum'])) {
                            unset($data[$key]);
                            continue;
                        }
                        $parentdata[$prod['parentId']] = [
                            'id' => $prod['parentId'],
                            'number' => $parents[$prod['parentId']]['number'],
                            'name' => $parents[$prod['parentId']]['name'] . ' ('. $parents[$prod['parentId']]['childCount'] . ')',
                            'sum' => $prod['sum'],
                            'returnSum' => $prod['returnSum'],
                            'part' => round((100*$prod['returnSum']) / $prod['sum'], 2),
                            'manufacturer' => $manufacturers[$parents[$prod['parentId']]['manufacturerId']] ? $manufacturers[$parents[$prod['parentId']]['manufacturerId']]->getTranslated()['name'] : ''
                        ];

                    } else {
                        $parentdata[$prod['parentId']]['sum'] += $prod['sum'];
                        $parentdata[$prod['parentId']]['returnSum'] += $prod['returnSum'];
                        $parentdata[$prod['parentId']]['part'] += round((100*$parentdata[$prod['parentId']]['returnSum']) / $parentdata[$prod['parentId']]['sum']);
                    }
                    unset($data[$key]);
                }
            }

            $data = array_merge(array_values($data), array_values($parentdata));
            //parents einsortieren
            $data = StatisticsHelper::sortArrayByColumn($data, $sortingField, $direction);
        }

        elseif (in_array($sortingField, ['returnSum', 'part','manufacturer'])) {
            //Sortierung der anderen Felder bereits im SQL
            $data = StatisticsHelper::sortArrayByColumn($data, $sortingField, $direction);
        }

        $gridData   = StatisticsHelper::limitData($data, $parameters['config']['gridLimit']);

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $gridData];
    }

    private function getProductSalesData(array $parameters, ?string $languageId, array $orderIdsBytes): array
    {
        $sortingField = $parameters['sortBy'] ?? 'sum';
        $sortingField = in_array($sortingField, ['number','name','sum']) ? $sortingField : 'sum';
        $direction = $parameters['sortDirection'] ?? 'DESC';

        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(lineitems.product_id)) as `id`',
                'products.product_number as number',
                'IFNULL(IFNULL(IFNULL(trans1.name, trans2.name), trans1Parent.name), trans2Parent.name) as name',
                'SUM(lineitems.quantity) as `sum`',
                'products.option_ids as optionIds',
                'LOWER(HEX(products.manufacturer)) as manufacturerId'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`product`', 'products', 'lineitems.product_id = products.id')
            ->leftJoin('products', 'product_translation', 'trans1',
                'products.id = trans1.product_id AND trans1.language_id = UNHEX(:language1)')
            ->leftJoin('products', 'product_translation', 'trans2',
                'products.id = trans2.product_id AND trans2.language_id = UNHEX(:language2)')
            ->leftJoin('products', 'product_translation', 'trans1Parent',
                'products.parent_id = trans1Parent.product_id AND trans1Parent.language_id = UNHEX(:language1)')
            ->leftJoin('products', 'product_translation', 'trans2Parent',
                'products.parent_id = trans2Parent.product_id AND trans2Parent.language_id = UNHEX(:language2)')
            ->andWhere('lineitems.version_id = :versionId')
            ->andWhere('lineitems.order_id IN (:orderIds)')
            ->andWhere('products.version_id = :versionId')
            ->andWhere('lineitems.order_id IS NOT NULL')
            ->andWhere('lineitems.quantity > 0')
            ->andWhere('IF(trans1.product_version_id IS NOT NULL, trans1.product_version_id = :versionId, 1)')
            ->andWhere('IF(trans2.product_version_id IS NOT NULL, trans2.product_version_id = :versionId, 1)')
            //->andWhere('lineitems.type = :itemtype')
            ->setParameters(
                [
                    'orderIds' => $orderIdsBytes,
                    'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    //'itemtype' => 'product',
                    'language1' => $languageId,
                    'language2' => Defaults::LANGUAGE_SYSTEM
                ],
                [
                    'orderIds' => ArrayParameterType::STRING
                ]
            )
            ->groupBy('`id`')
            ->orderBy($sortingField, $direction);

        if (!empty($parameters['showVariantParent'])) {
            $query->addSelect('LOWER(HEX(products.parent_id)) as parentId');
        }

        if (!empty($parameters['productSearchIds'])) {
            $parameters['productSearchIds'] = UUid::fromHexToBytesList($parameters['productSearchIds']);
            if (empty($parameters['showVariantParent'])) {
                $query->andWhere('lineitems.product_id IN (:productSearchIds)')
                    ->setParameter('productSearchIds', $parameters['productSearchIds'], ArrayParameterType::STRING);
            } else {
                $query->andWhere('lineitems.product_id IN (:productSearchIds) OR products.parent_id IN (:productSearchIds)')
                    ->setParameter('productSearchIds', $parameters['productSearchIds'], ArrayParameterType::STRING);
            }
        }

        if (!empty($parameters['manufacturerSearchIds'])) {
            $parameters['manufacturerSearchIds'] = UUid::fromHexToBytesList($parameters['manufacturerSearchIds']);
            $query->andWhere('products.manufacturer IN (:manufacturerSearchIds)')
                ->setParameter('manufacturerSearchIds', $parameters['manufacturerSearchIds'], ArrayParameterType::STRING);
        }

        return $query->fetchAllAssociative();
    }
}

