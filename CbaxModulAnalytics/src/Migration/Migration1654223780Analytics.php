<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1654223780Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1654223780;
    }

    public function update(Connection $connection): void
    {
        /*
         * klicks auf produkte -> Tabelle
         */
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `cbax_analytics_product_impressions` (
				`id` BINARY(16) NOT NULL,
				`product_id` BINARY(16) NOT NULL,
				`sales_channel_id` BINARY(16) NOT NULL,
				`date` DATE NOT NULL,
				`impressions` int(11) DEFAULT NULL,
				`device_type` varchar(50) DEFAULT NULL,
				`created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
				PRIMARY KEY (`id`),
                CONSTRAINT `fk.cbax_analytics_product_impressions.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.cbax_analytics_product_impressions.product_id` FOREIGN KEY (`product_id`)
                    REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `uniq.cbax_analytics_product_impressions.entity_id` UNIQUE (`product_id`, `sales_channel_id`, `date`, `device_type`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        /*
         * klicks auf Kategorien -> Tabelle
         */
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `cbax_analytics_category_impressions` (
				`id` BINARY(16) NOT NULL,
				`category_id` BINARY(16) NOT NULL,
				`sales_channel_id` BINARY(16) NOT NULL,
				`date` DATE NOT NULL,
				`impressions` int(11) DEFAULT NULL,
				`device_type` varchar(50) DEFAULT NULL,
				`created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
				PRIMARY KEY (`id`),
                CONSTRAINT `fk.cbax_analytics_category_impressions.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.cbax_analytics_category_impressions.category_id` FOREIGN KEY (`category_id`)
                    REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `uniq.cbax_analytics_category_impressions.entity_id` UNIQUE (`category_id`, `sales_channel_id`, `date`, `device_type`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        /*
         * klicks auf hersteller -> Tabelle
         */
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `cbax_analytics_manufacturer_impressions` (
				`id` BINARY(16) NOT NULL,
				`manufacturer_id` BINARY(16) NOT NULL,
				`sales_channel_id` BINARY(16) NOT NULL,
				`date` DATE NOT NULL,
				`impressions` int(11) DEFAULT NULL,
				`device_type` varchar(50) DEFAULT NULL,
				`created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
				PRIMARY KEY (`id`),
                CONSTRAINT `fk.cbax_analytics_manufacturer_impressions.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.cbax_analytics_manufacturer_impressions.manufacturer_id` FOREIGN KEY (`manufacturer_id`)
                    REFERENCES `product_manufacturer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `uniq.cbax_analytics_manufacturer_impressions.entity_id` UNIQUE (`manufacturer_id`, `sales_channel_id`, `date`, `device_type`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        /*
         * Tabelle für die gehashten IP-Adressen der Besucher pro tag
         */
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `cbax_analytics_pool` (
                `id` BINARY(16) NOT NULL,
                `date` DATE NOT NULL,
                `remote_address` varchar(255) NOT NULL,
                `sales_channel_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.cbax_analytics_pool.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `uniq.cbax_analytics_pool.remote_address` UNIQUE (`date`, `remote_address`, `sales_channel_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        /*
         * Referer-Tabelle
         */
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `cbax_analytics_referer` (
                `id` BINARY(16) NOT NULL,
                `date` DATE NOT NULL,
                `referer` varchar(70) DEFAULT NULL,
                `sales_channel_id` BINARY(16) NOT NULL,
                `counted` int(11) DEFAULT NULL,
                `device_type` varchar(255) DEFAULT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.cbax_analytics_referer.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `uniq.cbax_analytics_referer.referer` UNIQUE (`date`, `referer`, `sales_channel_id`, `device_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        /*
         * Besucher-Tabelle
         */
        $connection->executeStatement("
            CREATE TABLE IF NOT EXISTS `cbax_analytics_visitors` (
                `id` BINARY(16) NOT NULL,
                `sales_channel_id` BINARY(16) NOT NULL,
                `date` DATE NOT NULL,
                `page_impressions` int(11) DEFAULT NULL,
                `unique_visits` int(11) DEFAULT NULL,
                `device_type` varchar(50) DEFAULT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.cbax_analytics_visitors.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `uniq.cbax_analytics_visitors.referer` UNIQUE (`sales_channel_id`, `date`, `device_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        /*
         * visitors in der group-config-Tabelle anlegen
         */
        $configGroupFields = array (
            array('name' => 'visitors', 'label' => 'cbax-analytics.view.groups.visitors', 'position' => 9, 'active' => 1, 'parameter' => '')
        );

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $groupIds = [];

        foreach($configGroupFields as $field) {
            $sql = "SELECT `id` FROM `cbax_analytics_groups_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $randomId = Uuid::randomBytes();
            $groupIds[$field['name']] = $randomId;

            $connection->executeStatement('
                INSERT IGNORE INTO `cbax_analytics_groups_config`
                    (`id`, `name`, `label`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :label, :position, :active, :parameter, :created_at)',
                [
                    'id' => $randomId,
                    'name' => $field['name'],
                    'label' => $field['label'],
                    'position' => $field['position'],
                    'active' => $field['active'],
                    'parameter' => $field['parameter'],
                    'created_at' => $created_at
                ]
            );
        }

        /*
         * visitors in der config-Tabelle anlegen
         */
        $configFields = array (
            array('name' => 'visitors', 'group_name' => 'visitors', 'label' => 'cbax-analytics.view.visitors.titleTree', 'route_name' => 'cbax.analytics.getVisitors', 'path_info' => '/cbax/analytics/getVisitors', 'position' => 41, 'active' => 1, 'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 41}, "componentName": "cbax-analytics-index-visitors"}'),
            array('name' => 'visitor_impressions', 'group_name' => 'visitors', 'label' => 'cbax-analytics.view.visitorImpressions.titleTree', 'route_name' => 'cbax.analytics.getVisitorImpressions', 'path_info' => '/cbax/analytics/getVisitorImpressions', 'position' => 42, 'active' => 1, 'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 42}, "componentName": "cbax-analytics-index-visitor-impressions"}'),
            array('name' => 'referer', 'group_name' => 'visitors', 'label' => 'cbax-analytics.view.referer.titleTree', 'route_name' => 'cbax.analytics.getReferer', 'path_info' => '/cbax/analytics/getReferer', 'position' => 43, 'active' => 1, 'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 0, "showTable": 0, "showChart": 0, "position": 43}, "componentName": "cbax-analytics-index-referer"}'),
            array('name' => 'category_impressions', 'group_name' => 'visitors', 'label' => 'cbax-analytics.view.categoryImpressions.titleTree', 'route_name' => 'cbax.analytics.getCategoryImpressions', 'path_info' => '/cbax/analytics/getCategoryImpressions', 'position' => 44, 'active' => 1, 'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 44}, "componentName": "cbax-analytics-index-category-impressions"}'),
            array('name' => 'manufacturer_impressions', 'group_name' => 'visitors', 'label' => 'cbax-analytics.view.manufacturerImpressions.titleTree', 'route_name' => 'cbax.analytics.getManufacturerImpressions', 'path_info' => '/cbax/analytics/getManufacturerImpressions', 'position' => 45, 'active' => 1, 'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 45}, "componentName": "cbax-analytics-index-manufacturer-impressions"}')
        );

        foreach($configFields as $field) {

            $sql = "SELECT `id` FROM `cbax_analytics_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $groupId = !empty($groupIds[$field['group_name']]) ? $groupIds[$field['group_name']] : NULL;

            $connection->executeStatement('
                INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_id`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_id, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)',
                    [
                        'id' => Uuid::randomBytes(),
                        'name' => $field['name'],
                        'group_id' => $groupId,
                        'group_name' => $field['group_name'],
                        'label' => $field['label'],
                        'route_name' => $field['route_name'],
                        'path_info' => $field['path_info'],
                        'position' => $field['position'],
                        'active' => $field['active'],
                        'parameter' => $field['parameter'],
                        'created_at' => $created_at
                    ]
            );
        }

        $connection->executeStatement("UPDATE `cbax_analytics_config` as c
        SET c.group_name = 'visitors', c.active = 1, c.position = 46, c.parameter = JSON_SET(`parameter`, '$.dashboard.hasTable', 1, '$.dashboard.position', 46, '$.dashboard.hasChart', 1, '$.componentName', 'cbax-analytics-index-product-impressions'), c.group_id=(SELECT g.id FROM `cbax_analytics_groups_config` as g
            WHERE g.name='visitors' LIMIT 1) WHERE c.name IN
            ('product_impressions');
        ");
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}

