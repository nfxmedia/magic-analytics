<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

use Cbax\ModulAnalytics\Components\Base;

class SalesByTaxrate implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly Connection $connection
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'orders.price as price',
                'orders.tax_status as taxStatus'
            ])
            ->from('`order`', 'orders')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate']
            ]);

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $data = $query->fetchAllAssociative();

        foreach ($data as &$item) {
            $item['price'] = json_decode($item['price'], true);
        }
        unset($item);

        $taxData = [];
        foreach ($data as $item) {
            foreach ($item['price']['calculatedTaxes'] as $tax) {
                if (empty($taxData[$tax['taxRate']])) {
                    $taxData[$tax['taxRate']] = [
                        'taxRate' => $tax['taxRate'],
                        'tax' => $tax['tax'],
                        'sum' => ($item['taxStatus'] == 'gross') ? $tax['price'] - $tax['tax'] : $tax['price']
                    ];
                } else {
                    $taxData[$tax['taxRate']]['tax'] += $tax['tax'];
                    $taxData[$tax['taxRate']]['sum'] += ($item['taxStatus'] == 'gross') ? $tax['price'] - $tax['tax'] : $tax['price'];
                }
            }
        }

        $taxData = array_values($taxData);

        foreach ($taxData as &$taxItem) {
            $taxItem['tax'] = round($taxItem['tax'], 2);
            $taxItem['sum'] = round($taxItem['sum'], 2);
        }
        unset($taxItem);

        $taxData = StatisticsHelper::sortArrayByColumn($taxData, 'taxRate');

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($taxData, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'seriesData' => $taxData];
    }
}

