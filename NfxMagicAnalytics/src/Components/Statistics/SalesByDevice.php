<?php declare(strict_types = 1);

namespace Nfx\MagicAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;

use Nfx\MagicAnalytics\Components\Base;

class SalesByDevice implements StatisticsInterface
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
                'JSON_UNQUOTE(JSON_EXTRACT(JSON_EXTRACT(orders.custom_fields, "$.nfxStatistics"),"$.device")) as name',
                'COUNT(orders.id) as count'
            ])
            ->from('`order`', 'orders')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->andWhere('orders.custom_fields IS NOT NULL')
            ->andWhere('JSON_EXTRACT(JSON_EXTRACT(orders.custom_fields, "$.nfxStatistics"),"$.device") IS NOT NULL')
            ->setParameters([
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION)
            ])
            ->groupBy('name')
            ->orderBy('sum', 'DESC');

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross')
        {
            $query->addSelect([
                'SUM(orders.amount_total) as sum'
            ]);
        } else {
            $query->addSelect([
                'SUM(orders.amount_net) as sum'
            ]);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $data = $query->fetchAllAssociative();

        foreach ($data as &$set)
        {
            $set['sum'] = round((float)$set['sum'], 2);
            $set['count'] = (int)$set['count'];
            $set['name'] = ucfirst($set['name']);
        }
        unset($set);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridData' => $data, 'seriesData' => $data];
    }
}



