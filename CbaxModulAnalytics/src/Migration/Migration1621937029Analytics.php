<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1621937029Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1621937029;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `cbax_analytics_search` (
				`id` BINARY(16) NOT NULL,
				`search_term` varchar(255) NOT NULL,
				`results` int(4) DEFAULT NULL,
                `searched` int(8) DEFAULT NULL,
				`sales_channel_id` BINARY(16) NOT NULL,
				`created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
				PRIMARY KEY (`id`),
                CONSTRAINT `fk.cbax_analytics_search.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                 REFERENCES `sales_channel` (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement("
            UPDATE `cbax_analytics_config` SET `active` = 1, `position` = 41 WHERE `name` = 'search_terms';
        ");

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);



        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
                    [
                        'id' => Uuid::randomBytes(),
                        'name' => 'search_activity',
                        'group_name' => 'marketing',
                        'label' => 'cbax-analytics.view.searchActivity.titleTree',
                        'route_name' => 'cbax.analytics.getSearchActivity',
                        'path_info' => '/cbax/analytics/getSearchActivity',
                        'position' => 42,
                        'active' => 1,
                        'parameter' => '',
                        'created_at' => $created_at
                    ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'search_trends',
                'group_name' => 'marketing',
                'label' => 'cbax-analytics.view.searchTrends.titleTree',
                'route_name' => 'cbax.analytics.getSearchtTrends',
                'path_info' => '/cbax/analytics/getSearchTrends',
                'position' => 43,
                'active' => 0,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'sales_by_os',
                'group_name' => 'sales',
                'label' => 'cbax-analytics.view.salesByOs.titleTree',
                'route_name' => 'cbax.analytics.getSalesByOs',
                'path_info' => '/cbax/analytics/getSalesByOs',
                'position' => 44,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'sales_by_browser',
                'group_name' => 'sales',
                'label' => 'cbax-analytics.view.salesByBrowser.titleTree',
                'route_name' => 'cbax.analytics.getSalesByBrowser',
                'path_info' => '/cbax/analytics/getSalesByBrowser',
                'position' => 45,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'products_inventory',
                'group_name' => 'products',
                'label' => 'cbax-analytics.view.productsInventory.titleTree',
                'route_name' => 'cbax.analytics.getProductsInventory',
                'path_info' => '/cbax/analytics/getProductsInventory',
                'position' => 46,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'products_profit',
                'group_name' => 'products',
                'label' => 'cbax-analytics.view.productsProfit.titleTree',
                'route_name' => 'cbax.analytics.getProductsProfit',
                'path_info' => '/cbax/analytics/getProductsProfit',
                'position' => 47,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'variants_compare',
                'group_name' => 'sales',
                'label' => 'cbax-analytics.view.variantsCompare.titleTree',
                'route_name' => 'cbax.analytics.getVariantsCompare',
                'path_info' => '/cbax/analytics/getVariantsCompare',
                'position' => 48,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'product_stream',
                'group_name' => 'products',
                'label' => 'cbax-analytics.view.productStream.titleTree',
                'route_name' => 'cbax.analytics.getProductStream',
                'path_info' => '/cbax/analytics/getProductStream',
                'position' => 49,
                'active' => 0,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'sales_by_products_filter',
                'group_name' => 'products',
                'label' => 'cbax-analytics.view.salesByProductsFilter.titleTree',
                'route_name' => 'cbax.analytics.getSalesByProductsFilter',
                'path_info' => '/cbax/analytics/getSalesByProductsFilter',
                'position' => 50,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'sales_filter',
                'group_name' => 'sales',
                'label' => 'cbax-analytics.view.salesFilter.titleTree',
                'route_name' => 'cbax.analytics.getSalesFilter',
                'path_info' => '/cbax/analytics/getSalesFilter',
                'position' => 51,
                'active' => 0,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            UPDATE `cbax_analytics_config` SET `active` = 1 WHERE `name` = 'sales_by_device';
        ");

        $connection->executeStatement("UPDATE `cbax_analytics_config` as c
            SET c.group_id=(SELECT g.id FROM `cbax_analytics_groups_config` as g
                WHERE g.name=c.group_name LIMIT 1) WHERE c.name IN
                ('search_trends','search_activity','sales_by_os','sales_by_browser','products_inventory',
                'products_profit','variants_compare','product_stream','sales_by_products_filter','sales_filter');
        ");
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
