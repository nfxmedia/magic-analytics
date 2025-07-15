<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

class Migration1679899712Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1679899712;
    }

    public function update(Connection $connection): void
    {
        $configFields = [
            [
                'name' => 'sales_by_currency',
                'group_name' => 'sales',
                'label' => 'cbax-analytics.view.salesByCurrency.titleTree',
                'route_name' => 'cbax.analytics.getSalesByCurrency',
                'path_info' => '/cbax/analytics/getSalesByCurrency',
                'position' => 56,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 56}, "componentName": "cbax-analytics-index-sales-by-currency"}'
            ]
        ];

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($configFields as $field) {

            $sql = "SELECT `id` FROM `cbax_analytics_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $connection->executeStatement('
                INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_id`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, NULL, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)',
                [
                    'id' => Uuid::randomBytes(),
                    'name' => $field['name'],
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
            SET c.group_id=(SELECT g.id FROM `cbax_analytics_groups_config` as g
            WHERE g.name='sales' LIMIT 1) WHERE c.name IN
            ('sales_by_currency');
        ");

        if (!$this->columnExist('cbax_analytics_product_impressions', 'customer_group_id', $connection))
        {
            $sql = "
                ALTER TABLE `cbax_analytics_product_impressions` ADD `customer_group_id` BINARY(16) NULL default NULL AFTER `sales_channel_id`;
                ALTER TABLE `cbax_analytics_product_impressions` DROP FOREIGN KEY `fk.cbax_analytics_product_impressions.sales_channel_id`;
                ALTER TABLE `cbax_analytics_product_impressions` DROP FOREIGN KEY `fk.cbax_analytics_product_impressions.product_id`;
                ALTER TABLE `cbax_analytics_product_impressions` DROP INDEX `uniq.cbax_analytics_product_impressions.entity_id`;
                ALTER TABLE `cbax_analytics_product_impressions` ADD CONSTRAINT `fk.cbax_analytics_product_impressions.customer_group_id`
                    FOREIGN KEY (`customer_group_id`) REFERENCES `customer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ALTER TABLE `cbax_analytics_product_impressions` ADD CONSTRAINT `fk.cbax_analytics_product_impressions.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ALTER TABLE `cbax_analytics_product_impressions` ADD CONSTRAINT `fk.cbax_analytics_product_impressions.product_id` FOREIGN KEY (`product_id`)
                    REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ALTER TABLE `cbax_analytics_product_impressions` ADD CONSTRAINT `uniq.cbax_analytics_product_impressions.entity_id`
                    UNIQUE (`product_id`, `sales_channel_id`, `date`, `device_type`, `customer_group_id`);
                ";
            $connection->executeStatement($sql);
        }

        if (!$this->columnExist('cbax_analytics_category_impressions', 'customer_group_id', $connection))
        {
            $sql = "
                ALTER TABLE `cbax_analytics_category_impressions` ADD `customer_group_id` BINARY(16) NULL default NULL AFTER `sales_channel_id`;
                ALTER TABLE `cbax_analytics_category_impressions` DROP FOREIGN KEY `fk.cbax_analytics_category_impressions.sales_channel_id`;
                ALTER TABLE `cbax_analytics_category_impressions` DROP FOREIGN KEY `fk.cbax_analytics_category_impressions.category_id`;
                ALTER TABLE `cbax_analytics_category_impressions` DROP INDEX `uniq.cbax_analytics_category_impressions.entity_id`;
                ALTER TABLE `cbax_analytics_category_impressions` ADD CONSTRAINT `fk.cbax_analytics_category_impressions.customer_group_id`
                    FOREIGN KEY (`customer_group_id`) REFERENCES `customer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ALTER TABLE `cbax_analytics_category_impressions` ADD CONSTRAINT `fk.cbax_analytics_category_impressions.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ALTER TABLE `cbax_analytics_category_impressions` ADD CONSTRAINT `fk.cbax_analytics_category_impressions.category_id` FOREIGN KEY (`category_id`)
                    REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ALTER TABLE `cbax_analytics_category_impressions` ADD CONSTRAINT `uniq.cbax_analytics_category_impressions.entity_id`
                    UNIQUE (`category_id`, `sales_channel_id`, `date`, `device_type`, `customer_group_id`);
                ";
            $connection->executeStatement($sql);
        }

        if (!$this->columnExist('cbax_analytics_manufacturer_impressions', 'customer_group_id', $connection))
        {
            $sql = "
                ALTER TABLE `cbax_analytics_manufacturer_impressions` ADD `customer_group_id` BINARY(16) NULL default NULL AFTER `sales_channel_id`;
                ALTER TABLE `cbax_analytics_manufacturer_impressions` DROP FOREIGN KEY `fk.cbax_analytics_manufacturer_impressions.sales_channel_id`;
                ALTER TABLE `cbax_analytics_manufacturer_impressions` DROP FOREIGN KEY `fk.cbax_analytics_manufacturer_impressions.manufacturer_id`;
                ALTER TABLE `cbax_analytics_manufacturer_impressions` DROP INDEX `uniq.cbax_analytics_manufacturer_impressions.entity_id`;
                ALTER TABLE `cbax_analytics_manufacturer_impressions` ADD CONSTRAINT `fk.cbax_analytics_manufacturer_impressions.customer_group_id`
                    FOREIGN KEY (`customer_group_id`) REFERENCES `customer_group` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ALTER TABLE `cbax_analytics_manufacturer_impressions` ADD CONSTRAINT `fk.cbax_analytics_manufacturer_impressions.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                    REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ALTER TABLE `cbax_analytics_manufacturer_impressions` ADD CONSTRAINT `fk.cbax_analytics_manufacturer_impressions.manufacturer_id` FOREIGN KEY (`manufacturer_id`)
                    REFERENCES `product_manufacturer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ALTER TABLE `cbax_analytics_manufacturer_impressions` ADD CONSTRAINT `uniq.cbax_analytics_manufacturer_impressions.entity_id`
                    UNIQUE (`manufacturer_id`, `sales_channel_id`, `date`, `device_type`, `customer_group_id`);
                ";
            $connection->executeStatement($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {

    }

    /**
     * Internal helper function to check if a database table column exist
     * @param string $tableName
     * @param string $columnName
     * @param object $connection
     * @return bool
     */
    private function columnExist($tableName, $columnName, $connection)
    {
        $sql = "SHOW COLUMNS FROM " . $connection->quoteIdentifier($tableName) . " LIKE ?";

        return count($connection->executeQuery($sql, [$columnName])->fetchAllAssociative()) > 0;
    }
}

