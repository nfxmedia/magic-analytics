<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Migration;

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
            CREATE TABLE IF NOT EXISTS `nfx_analytics_search` (
				`id` BINARY(16) NOT NULL,
				`search_term` varchar(255) NOT NULL,
				`results` int(4) DEFAULT NULL,
                `searched` int(8) DEFAULT NULL,
				`sales_channel_id` BINARY(16) NOT NULL,
				`created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
				PRIMARY KEY (`id`),
                CONSTRAINT `fk.nfx_analytics_search.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                 REFERENCES `sales_channel` (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeStatement("
            UPDATE `nfx_analytics_config` SET `active` = 1, `position` = 41 WHERE `name` = 'search_terms';
        ");

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);



        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
                    [
                        'id' => Uuid::randomBytes(),
                        'name' => 'search_activity',
                        'group_name' => 'marketing',
                        'label' => 'nfx-analytics.view.searchActivity.titleTree',
                        'route_name' => 'nfx.analytics.getSearchActivity',
                        'path_info' => '/nfx/analytics/getSearchActivity',
                        'position' => 42,
                        'active' => 1,
                        'parameter' => '',
                        'created_at' => $created_at
                    ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'search_trends',
                'group_name' => 'marketing',
                'label' => 'nfx-analytics.view.searchTrends.titleTree',
                'route_name' => 'nfx.analytics.getSearchtTrends',
                'path_info' => '/nfx/analytics/getSearchTrends',
                'position' => 43,
                'active' => 0,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'sales_by_os',
                'group_name' => 'sales',
                'label' => 'nfx-analytics.view.salesByOs.titleTree',
                'route_name' => 'nfx.analytics.getSalesByOs',
                'path_info' => '/nfx/analytics/getSalesByOs',
                'position' => 44,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'sales_by_browser',
                'group_name' => 'sales',
                'label' => 'nfx-analytics.view.salesByBrowser.titleTree',
                'route_name' => 'nfx.analytics.getSalesByBrowser',
                'path_info' => '/nfx/analytics/getSalesByBrowser',
                'position' => 45,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'products_inventory',
                'group_name' => 'products',
                'label' => 'nfx-analytics.view.productsInventory.titleTree',
                'route_name' => 'nfx.analytics.getProductsInventory',
                'path_info' => '/nfx/analytics/getProductsInventory',
                'position' => 46,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'products_profit',
                'group_name' => 'products',
                'label' => 'nfx-analytics.view.productsProfit.titleTree',
                'route_name' => 'nfx.analytics.getProductsProfit',
                'path_info' => '/nfx/analytics/getProductsProfit',
                'position' => 47,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'variants_compare',
                'group_name' => 'sales',
                'label' => 'nfx-analytics.view.variantsCompare.titleTree',
                'route_name' => 'nfx.analytics.getVariantsCompare',
                'path_info' => '/nfx/analytics/getVariantsCompare',
                'position' => 48,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'product_stream',
                'group_name' => 'products',
                'label' => 'nfx-analytics.view.productStream.titleTree',
                'route_name' => 'nfx.analytics.getProductStream',
                'path_info' => '/nfx/analytics/getProductStream',
                'position' => 49,
                'active' => 0,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'sales_by_products_filter',
                'group_name' => 'products',
                'label' => 'nfx-analytics.view.salesByProductsFilter.titleTree',
                'route_name' => 'nfx.analytics.getSalesByProductsFilter',
                'path_info' => '/nfx/analytics/getSalesByProductsFilter',
                'position' => 50,
                'active' => 1,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)",
            [
                'id' => Uuid::randomBytes(),
                'name' => 'sales_filter',
                'group_name' => 'sales',
                'label' => 'nfx-analytics.view.salesFilter.titleTree',
                'route_name' => 'nfx.analytics.getSalesFilter',
                'path_info' => '/nfx/analytics/getSalesFilter',
                'position' => 51,
                'active' => 0,
                'parameter' => '',
                'created_at' => $created_at
            ]
        );

        $connection->executeStatement("
            UPDATE `nfx_analytics_config` SET `active` = 1 WHERE `name` = 'sales_by_device';
        ");

        $connection->executeStatement("UPDATE `nfx_analytics_config` as c
            SET c.group_id=(SELECT g.id FROM `nfx_analytics_groups_config` as g
                WHERE g.name=c.group_name LIMIT 1) WHERE c.name IN
                ('search_trends','search_activity','sales_by_os','sales_by_browser','products_inventory',
                'products_profit','variants_compare','product_stream','sales_by_products_filter','sales_filter');
        ");
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
