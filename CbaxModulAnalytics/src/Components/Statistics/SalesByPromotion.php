<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Components\Statistics;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Cbax\ModulAnalytics\Components\Base;

class SalesByPromotion implements StatisticsInterface
{
    public function __construct(
        private readonly Base $base,
        private readonly Connection $connection
    ) {

    }

    public function getStatisticsData(array $parameters, Context $context): array
    {
        $languageId = $this->base->getLanguageIdByLocaleCode($parameters['adminLocalLanguage'], $context);

        //Gruppiert nach Aktionscode
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(lineitems.promotion_id)) as `id`',
                'lineitems.referenced_id as code',
                'IF(trans1.name IS NOT NULL, trans1.name, trans2.name) as name',
                'SUM(lineitems.total_price/orders.currency_factor) as `discount`',
                'SUM(lineitems.quantity) as `count`'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', 'promotion', 'promotion', 'lineitems.promotion_id = promotion.id')
            ->leftJoin('promotion', 'promotion_translation', 'trans1',
                'promotion.id = trans1.promotion_id AND trans1.language_id = UNHEX(:language1)')
            ->leftJoin('promotion', 'promotion_translation', 'trans2',
                'promotion.id = trans2.promotion_id AND trans2.language_id = UNHEX(:language2)')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->andWhere('lineitems.type = :itemtype')
            ->andWhere('lineitems.referenced_id IS NOT NULL')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                'itemtype' => 'promotion',
                'language1' => $languageId,
                'language2' => Defaults::LANGUAGE_SYSTEM
            ])
            ->groupBy('lineitems.referenced_id')
            ->orderBy('`sum`', 'DESC');

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross')
        {
            $query->addSelect([
                "SUM(orders.amount_total/orders.currency_factor) as `sum`"
            ]);
        } else {

            $query->addSelect([
                "SUM(orders.amount_net/orders.currency_factor) as `sum`"
            ]);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $data = $query->fetchAllAssociative();

        foreach($data as &$promotion)
        {
            $promotion['count'] = (int)$promotion['count'];
            $promotion['sum'] = round((float)$promotion['sum'], 2);
            $promotion['discount'] = round((float)$promotion['discount'], 2);
            $promotion['avg'] = round((float)$promotion['sum']/(float)$promotion['count'], 2);
        }
        unset($promotion);

        $gridDataCode = array_slice($data, 0, $parameters['config']['gridLimit']);
        $seriesDataCode = array_slice($data, 0, $parameters['config']['chartLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        //Gruppiert nach RabattAktion
        $qb = $this->connection->createQueryBuilder();
        $query = $qb
            ->select([
                'LOWER(HEX(lineitems.promotion_id)) as `id`',
                'lineitems.referenced_id as code',
                'IF(trans1.name IS NOT NULL, trans1.name, trans2.name) as name',
                'SUM(lineitems.total_price/orders.currency_factor) as `discount`',
                'SUM(lineitems.quantity) as `count`'
            ])
            ->from('order_line_item', 'lineitems')
            ->innerJoin('lineitems', '`order`', 'orders', 'lineitems.order_id = orders.id')
            ->innerJoin('lineitems', 'promotion', 'promotion', 'lineitems.promotion_id = promotion.id')
            ->leftJoin('promotion', 'promotion_translation', 'trans1',
                'promotion.id = trans1.promotion_id AND trans1.language_id = UNHEX(:language1)')
            ->leftJoin('promotion', 'promotion_translation', 'trans2',
                'promotion.id = trans2.promotion_id AND trans2.language_id = UNHEX(:language2)')
            ->andWhere('lineitems.version_id = :versionId')
            //->andWhere('lineitems.order_version_id = :versionId')
            ->andWhere('orders.version_id = :versionId')
            ->andWhere('orders.order_date_time >= :start')
            ->andWhere('orders.order_date_time <= :end')
            ->andWhere('lineitems.type = :itemtype')
            ->andWhere('lineitems.referenced_id IS NOT NULL')
            ->setParameters([
                'versionId' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                'start' => $parameters['startDate'],
                'end' => $parameters['endDate'],
                'itemtype' => 'promotion',
                'language1' => $languageId,
                'language2' => Defaults::LANGUAGE_SYSTEM
            ])
            ->groupBy('`id`')
            ->orderBy('`sum`', 'DESC');

        if (!empty($parameters['config']['grossOrNet']) && $parameters['config']['grossOrNet'] == 'gross')
        {
            $query->addSelect([
                "SUM(orders.amount_total/orders.currency_factor) as `sum`"
            ]);
        } else {

            $query->addSelect([
                "SUM(orders.amount_net/orders.currency_factor) as `sum`"
            ]);
        }

        $query = $this->base->setMoreQueryConditions($query, $parameters, $context);

        $data = $query->fetchAllAssociative();

        foreach($data as &$promotion)
        {
            $promotion['count'] = (int)$promotion['count'];
            $promotion['sum'] = round((float)$promotion['sum'], 2);
            $promotion['discount'] = round((float)$promotion['discount'], 2);
            $promotion['avg'] = round((float)$promotion['sum']/(float)$promotion['count'], 2);
        }
        unset($promotion);

        $gridDataPromotion = array_slice($data, 0, $parameters['config']['gridLimit']);
        $seriesDataPromotion = array_slice($data, 0, $parameters['config']['chartLimit']);

        if ($parameters['format'] === 'csv') {
            return ['success' => true, 'fileSize' => $this->base->exportCSV($data, $parameters['labels'], $parameters['config'])];
        }

        return ['success' => true, 'gridDataCode' => $gridDataCode, 'seriesDataCode' => $seriesDataCode, 'gridDataPromotion' => $gridDataPromotion, 'seriesDataPromotion' => $seriesDataPromotion];
    }
}



