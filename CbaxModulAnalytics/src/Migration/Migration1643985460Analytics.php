<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\ArrayParameterType;

class Migration1643985460Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1643985460;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `cbax_analytics_config` DROP COLUMN `parameter`;
        ');

        $connection->executeStatement('
            ALTER TABLE `cbax_analytics_config` ADD COLUMN `parameter` JSON NULL DEFAULT NULL AFTER `active`;
        ');

        $hasTable = '{"dashboard": {"hasTable": 1, "hasChart": 0, "showTable": 0, "showChart": 0}}';
        $hasBoth = '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0}}';
        $hasNone = '{"dashboard": {"hasTable": 0, "hasChart": 0, "showTable": 0, "showChart": 0}}';
        $statsOnlyTable = [
            'quick_overview',
            'product_high_instock',
            'product_inactive_with_instock',
            'products_inventory',
            'products_profit',
            'unfinished_orders',
            'search_terms',
            'product_low_instock',
            'product_soon_outstock'
        ];
        $statsBoth = [
            'sales_by_category',
            'sales_by_language',
            'sales_by_saleschannel',
            'sales_by_promotion',
            'count_by_products',
            'orders_count_all',
            'sales_all',
            'sales_by_os',
            'sales_by_payment_method',
            'unfinished_orders_by_payment',
            'sales_by_shipping_method',
            'sales_by_month',
            'sales_by_products',
            'orders_by_status',
            'orders_by_delivery_status',
            'product_by_orders',
            'sales_by_manufacturer',
            'sales_by_time',
            'search_activity',
            'orders_by_transaction_status',
            'new_customers_by_time',
            'sales_by_country',
            'customer_age',
            'sales_by_browser',
            'sales_by_promotion_others',
            'sales_by_affiliate',
            'sales_by_device',
            'sales_by_customergroups',
            'sales_by_customer',
            'sales_by_weekdays',
            'sales_by_campaign',
            'unfinished_orders_by_cart',
            'sales_by_category',
            'count_by_products',
            'orders_count_all',
            'canceled_orders_by_month'
        ];

        $connection->executeStatement(
            'UPDATE `cbax_analytics_config` SET `parameter` = :hasBoth WHERE `name` IN (:stats);',
            ['hasBoth' => $hasBoth, 'stats' => $statsBoth], ['stats' => ArrayParameterType::STRING]
        );

        $connection->executeStatement(
            'UPDATE `cbax_analytics_config` SET `parameter` = :hasTable WHERE `name` IN (:stats);',
            ['hasTable' => $hasTable, 'stats' => $statsOnlyTable], ['stats' => ArrayParameterType::STRING]
        );

        $connection->executeStatement(
            'UPDATE `cbax_analytics_config` SET `parameter` = :hasNone WHERE `parameter` IS NULL;',
            ['hasNone' => $hasNone]
        );

        $allStats = $connection->executeQuery('SELECT `name`, `position` FROM `cbax_analytics_config` WHERE 1;')->fetchAllAssociative();

        foreach ($allStats as $stat)
        {
            $componentName = 'cbax-analytics-index-' . str_replace('_', '-', $stat['name']);
            $connection->executeStatement(
                'UPDATE `cbax_analytics_config`
                        SET `parameter` = JSON_SET(`parameter`, "$.componentName", :compName, "$.dashboard.position", :position)
                        WHERE `name` = :statName;',
                ['compName' => $componentName, 'statName' => $stat['name'], 'position' => $stat['position']]
            );
        }

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'sales_by_billing_country',
                'group_name' => 'sales',
                'label' => 'cbax-analytics.view.salesByBillingCountry.titleTree',
                'route_name' => 'cbax.analytics.getSalesByBillingCountry',
                'path_info' => '/cbax/analytics/getSalesByBillingCountry',
                'position' => 10,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 10}, "componentName": "cbax-analytics-index-sales-by-billing-country"}',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("UPDATE `cbax_analytics_config` as c
            SET c.group_id=(SELECT g.id FROM `cbax_analytics_groups_config` as g
                WHERE g.name=c.group_name LIMIT 1) WHERE c.name IN
                ('sales_by_billing_country');
        ");

        $connection->executeStatement("UPDATE `cbax_analytics_config`
            SET active = 1, `position` = 3 WHERE name = 'orders_count_all';
        ");

    }

    public function updateDestructive(Connection $connection): void
    {

    }
}

