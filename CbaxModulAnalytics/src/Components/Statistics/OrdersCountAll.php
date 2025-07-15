<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\DateHistogramAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

use Cbax\ModulAnalytics\Components\Base;

class OrdersCountAll implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly EntityRepository $orderRepository,
        private readonly Connection $connection
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $criteria = StatisticsHelper::getBaseCriteria('orderDateTime', $parameters);
        $filters = StatisticsHelper::getMoreFilters($parameters);

        $criteria->addAggregation(
            new FilterAggregation(
                'filter-order_count_day',
                new DateHistogramAggregation(
                    'order_count_day',
                    'orderDate',
                    DateHistogramAggregation::PER_DAY
                ),
                $filters
            )
        );

        $result = $this->orderRepository->search($criteria, $context);
        $aggregation = $result->getAggregations()->get('order_count_day');

        //Erstbestellungen
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'orders.order_date as date',
                'COUNT(orders.id) as count'
            ])
            ->from('`order`', 'orders')

            ->innerJoin('orders', 'order_customer', 'orderCustomer', 'orders.id = orderCustomer.order_id')
            ->andWhere('orderCustomer.version_id = :versionId')
            ->andWhere('orderCustomer.order_version_id = :versionId')
            ->andWhere('NOT ((SELECT count(ocs.id) FROM order_customer as ocs
                                    INNER JOIN `order` as o
                                    ON ocs.order_id = o.id AND o.version_id = :versionId
                                    WHERE orderCustomer.customer_id = ocs.customer_id AND
                                    o.order_date_time < orders.order_date_time AND
                                    ocs.version_id = :versionId AND
                                    ocs.order_version_id = :versionId AND
                                    ocs.order_id != orders.id) > 0)')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->setParameters([
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION)
            ])
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $firstTimedata = $query->fetchAllAssociative();
        $firstTimeOrders = [];

        if (!empty($firstTimedata))
        {
            foreach ($firstTimedata as $item)
            {
                $firstTimeOrders[$item['date']] = $item['count'];
            }
        }

        $data = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            $rawDate = explode(' ', $bucket->getKey())[0];
            $firstTimeCount = !empty($firstTimeOrders[$rawDate]) ? (int)$firstTimeOrders[$rawDate] : 0;
            $allCount = (int)$bucket->getCount();
            $firstTimeCount = min($firstTimeCount, $allCount);
            $data[] = [
                'date' => $rawDate,
                'formatedDate' => StatisticsHelper::getFormatedDate($bucket->getKey(), $parameters['adminLocalLanguage']),
                'firstTimeCount' => $firstTimeCount,
                'returningCount' => $allCount - $firstTimeCount,
                'count' => $allCount
            ];
        }

        if ($parameters['format'] === 'csv') {
            return ["success" => true, "fileSize" => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ["success" => true, "seriesData" => $data];
    }
}
