<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1608027541Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1608027541;
    }

    public function update(Connection $connection): void
    {
        if ($this->tableExist('cbax_analytics_config', $connection))
        {
            if (!$this->columnExist('cbax_analytics_config', 'group_name', $connection)
                    || !$this->columnExist('cbax_analytics_config', 'group_id', $connection))
            {
                $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_config`');
            }

        }

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `cbax_analytics_groups_config` (
				`id` BINARY(16) NOT NULL,
				`name` varchar(255) NOT NULL,
				`label` varchar(255) NOT NULL,
				`position` int(4) DEFAULT NULL,
				`active` int(1) NOT NULL,
				`parameter` varchar(255) DEFAULT NULL,
				`created_at` DATETIME(3) NOT NULL,
                                `updated_at` DATETIME(3) NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY(`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $configGroupFields = array (
            array('name' => 'sales', 'label' => 'cbax-analytics.view.groups.sales', 'position' => 1, 'active' => 1, 'parameter' => ''),
            array('name' => 'customers', 'label' => 'cbax-analytics.view.groups.customers', 'position' => 5, 'active' => 1, 'parameter' => ''),
            array('name' => 'products', 'label' => 'cbax-analytics.view.groups.products', 'position' => 4, 'active' => 1, 'parameter' => ''),
            array('name' => 'marketing', 'label' => 'cbax-analytics.view.groups.marketing', 'position' => 3, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders', 'label' => 'cbax-analytics.view.groups.orders', 'position' => 2, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished', 'label' => 'cbax-analytics.view.groups.unfinished', 'position' => 6, 'active' => 1, 'parameter' => ''),
            array('name' => 'lexicon', 'label' => 'cbax-analytics.view.groups.lexicon', 'position' => 7, 'active' => 0, 'parameter' => ''),
            array('name' => 'others', 'label' => 'cbax-analytics.view.groups.others', 'position' => 8, 'active' => 0, 'parameter' => '')
        );

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach($configGroupFields as $field) {

            $sql = "SELECT `id` FROM `cbax_analytics_groups_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $connection->executeStatement('
                INSERT IGNORE INTO `cbax_analytics_groups_config`
                    (`id`, `name`, `label`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :label, :position, :active, :parameter, :created_at)',
                [
                    'id' => Uuid::randomBytes(),
                    'name' => $field['name'],
                    'label' => $field['label'],
                    'position' => $field['position'],
                    'active' => $field['active'],
                    'parameter' => $field['parameter'],
                    'created_at' => $created_at
                ]
            );
        }

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `cbax_analytics_config` (
                            `id` BINARY(16) NOT NULL,
                            `name` varchar(255) NOT NULL,
                            `group_id` BINARY(16) DEFAULT NULL,
                            `group_name` varchar(255) DEFAULT NULL,
                            `label` varchar(255) NOT NULL,
                            `route_name` varchar(100) DEFAULT NULL,
                            `path_info` varchar(255) NOT NULL,
                            `position` int(4) DEFAULT NULL,
                            `active` int(1) NOT NULL,
                            `parameter` varchar(255) DEFAULT NULL,
                            `created_at` DATETIME(3) NOT NULL,
                            `updated_at` DATETIME(3) NULL,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY(`name`),
                            CONSTRAINT `fk.cbax_analytics_config.group_id` FOREIGN KEY (`group_id`)
                            REFERENCES `cbax_analytics_groups_config` (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $configFields = array (
            array('name' => 'sales_all', 'group_name' => 'orders', 'label' => 'cbax-analytics.view.orderSales.titleTree', 'route_name' => 'cbax.analytics.getSalesAll', 'path_info' => '/cbax/analytics/getSalesAll', 'position' => 2, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders_count_all', 'group_name' => 'orders', 'label' => 'cbax-analytics.view.orderCountAll.titleTree', 'route_name' => 'cbax.analytics.getOrdersCountAll', 'path_info' => '/cbax/analytics/getOrdersCountAll', 'position' => 1, 'active' => 0, 'parameter' => ''),
            array('name' => 'sales_by_manufacturer', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesByManufacturer.titleTree', 'route_name' => 'cbax.analytics.getSalesByManufacturer', 'path_info' => '/cbax/analytics/getSalesByManufacturer', 'position' => 7, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_month', 'group_name' => 'orders', 'label' => 'cbax-analytics.view.orderSalesMonthly.titleTree', 'route_name' => 'cbax.analytics.getSalesByMonth', 'path_info' => '/cbax/analytics/getSalesByMonth', 'position' => 3, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_shipping_method', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesByShippingMethod.titleTree', 'route_name' => 'cbax.analytics.getSalesByShipping', 'path_info' => '/cbax/analytics/getSalesByShipping', 'position' => 8, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_payment_method', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesByPaymentMethod.titleTree', 'route_name' => 'cbax.analytics.getSalesByPayment', 'path_info' => '/cbax/analytics/getSalesByPayment', 'position' => 9, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_country', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesByCountry.titleTree', 'route_name' => 'cbax.analytics.getSalesByCountry', 'path_info' => '/cbax/analytics/getSalesByCountry', 'position' => 10, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_language', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesByLanguage.titleTree', 'route_name' => 'cbax.analytics.getSalesByLanguage', 'path_info' => '/cbax/analytics/getSalesByLanguage', 'position' => 11, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_products', 'group_name' => 'products', 'label' => 'cbax-analytics.view.salesByProducts.titleTree', 'route_name' => 'cbax.analytics.getSalesByProducts', 'path_info' => '/cbax/analytics/getSalesByProducts', 'position' => 16, 'active' => 1, 'parameter' => ''),
            array('name' => 'count_by_products', 'group_name' => 'products', 'label' => 'cbax-analytics.view.countByProducts.titleTree', 'route_name' => 'cbax.analytics.getCountByProducts', 'path_info' => '/cbax/analytics/getCountByProducts', 'position' => 17, 'active' => 0, 'parameter' => ''),
            array('name' => 'sales_by_saleschannel', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesBySaleschannel.titleTree', 'route_name' => 'cbax.analytics.getSalesBySaleschannels', 'path_info' => '/cbax/analytics/getSalesBySaleschannels', 'position' => 12, 'active' => 1, 'parameter' => ''),
            array('name' => 'customer_age', 'group_name' => 'customers', 'label' => 'cbax-analytics.view.customerAge.titleTree', 'route_name' => 'cbax.analytics.getCustomerAge', 'path_info' => '/cbax/analytics/getCustomerAge', 'position' => 14, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_affiliate', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesByAffiliate.titleTree', 'route_name' => 'cbax.analytics.getSalesByAffiliates', 'path_info' => '/cbax/analytics/getSalesByAffiliates', 'position' => 6, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_campaign', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesByCampaign.titleTree', 'route_name' => 'cbax.analytics.getSalesByCampaign', 'path_info' => '/cbax/analytics/getSalesByCampaign', 'position' => 15, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_weekdays', 'group_name' => 'orders', 'label' => 'cbax-analytics.view.salesByWeekdays.titleTree', 'route_name' => 'cbax.analytics.getSalesByWeekdays', 'path_info' => '/cbax/analytics/getSalesByWeekdays', 'position' => 4, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_time', 'group_name' => 'orders', 'label' => 'cbax-analytics.view.salesByTime.titleTree', 'route_name' => 'cbax.analytics.getSalesByTime', 'path_info' => '/cbax/analytics/getSalesByTime', 'position' => 5, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_customergroups', 'group_name' => 'customers', 'label' => 'cbax-analytics.view.salesByCustomergroups.titleTree', 'route_name' => 'cbax.analytics.getSalesByCustomergroups', 'path_info' => '/cbax/analytics/getSalesByCustomergroups', 'position' => 13, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_category', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesByCategory.titleTree', 'route_name' => 'cbax.analytics.getSalesByCategory', 'path_info' => '/cbax/analytics/getSalesByCategory', 'position' => 18, 'active' => 0, 'parameter' => ''),
            array('name' => 'lexicon_impressions', 'group_name' => 'lexicon', 'label' => 'cbax-analytics.view.lexiconImpressions.titleTree', 'route_name' => 'cbax.analytics.getLexiconImpressions', 'path_info' => '/cbax/analytics/getLexiconImpressions', 'position' => 19, 'active' => 0, 'parameter' => ''),
            array('name' => 'sales_by_device', 'group_name' => 'sales', 'label' => 'cbax-analytics.view.salesByDevice.titleTree', 'route_name' => 'cbax.analytics.getSalesByDevice', 'path_info' => '/cbax/analytics/getSalesByDevice', 'position' => 20, 'active' => 0, 'parameter' => ''),
            array('name' => 'sales_by_promotion', 'group_name' => 'marketing', 'label' => 'cbax-analytics.view.salesByPromotion.titleTree', 'route_name' => 'cbax.analytics.getSalesByPromotion', 'path_info' => '/cbax/analytics/getSalesByPromotion', 'position' => 21, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_promotion_others', 'group_name' => 'marketing', 'label' => 'cbax-analytics.view.salesByPromotionOthers.titleTree', 'route_name' => 'cbax.analytics.getSalesByPromotionOthers', 'path_info' => '/cbax/analytics/getSalesByPromotionOthers', 'position' => 29, 'active' => 1, 'parameter' => ''),
            array('name' => 'search_terms', 'group_name' => 'marketing', 'label' => 'cbax-analytics.view.searchTerms.titleTree', 'route_name' => 'cbax.analytics.getSearchTerms', 'path_info' => '/cbax/analytics/getSearchTerms', 'position' => 22, 'active' => 0, 'parameter' => ''),
            array('name' => 'product_impressions', 'group_name' => 'products', 'label' => 'cbax-analytics.view.productImpressions.titleTree', 'route_name' => 'cbax.analytics.getProductImpressions', 'path_info' => '/cbax/analytics/getProductImpressions', 'position' => 23, 'active' => 0, 'parameter' => ''),
            array('name' => 'product_low_instock', 'group_name' => 'products', 'label' => 'cbax-analytics.view.productLowInstock.titleTree', 'route_name' => 'cbax.analytics.getProductLowInstock', 'path_info' => '/cbax/analytics/getProductLowInstock', 'position' => 24, 'active' => 1, 'parameter' => ''),
            array('name' => 'product_inactive_with_instock', 'group_name' => 'products', 'label' => 'cbax-analytics.view.productInactiveWithInstock.titleTree', 'route_name' => 'cbax.analytics.getProductInactiveWithInstock', 'path_info' => '/cbax/analytics/getProductInactiveWithInstock', 'position' => 25, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders_by_status', 'group_name' => 'orders', 'label' => 'cbax-analytics.view.ordersByStatus.titleTree', 'route_name' => 'cbax.analytics.getOrdersByStatus', 'path_info' => '/cbax/analytics/getOrdersByStatus', 'position' => 26, 'active' => 1, 'parameter' => ''),
            array('name' => 'product_high_instock', 'group_name' => 'products', 'label' => 'cbax-analytics.view.productHighInstock.titleTree', 'route_name' => 'cbax.analytics.getProductHighInstock', 'path_info' => '/cbax/analytics/getProductHighInstock', 'position' => 27, 'active' => 1, 'parameter' => ''),
            array('name' => 'product_soon_outstock', 'group_name' => 'products', 'label' => 'cbax-analytics.view.productSoonOutstock.titleTree', 'route_name' => 'cbax.analytics.getProductSoonOutstock', 'path_info' => '/cbax/analytics/getProductSoonOutstock', 'position' => 28, 'active' => 1, 'parameter' => ''),
            array('name' => 'product_by_orders', 'group_name' => 'products', 'label' => 'cbax-analytics.view.productByOrders.titleTree', 'route_name' => 'cbax.analytics.getProductByOrders', 'path_info' => '/cbax/analytics/getProductByOrders', 'position' => 30, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_customer', 'group_name' => 'customers', 'label' => 'cbax-analytics.view.salesByCustomer.titleTree', 'route_name' => 'cbax.analytics.getSalesByCustomer', 'path_info' => '/cbax/analytics/getSalesByCustomer', 'position' => 31, 'active' => 1, 'parameter' => ''),
            array('name' => 'new_customers_by_time', 'group_name' => 'customers', 'label' => 'cbax-analytics.view.newCustomersByTime.titleTree', 'route_name' => 'cbax.analytics.getNewCustomersByTime', 'path_info' => '/cbax/analytics/getNewCustomersByTime', 'position' => 32, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders_by_transaction_status', 'group_name' => 'orders', 'label' => 'cbax-analytics.view.ordersByTransactionStatus.titleTree', 'route_name' => 'cbax.analytics.getOrdersByTransactionStatus', 'path_info' => '/cbax/analytics/getOrdersByTransactionStatus', 'position' => 33, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders_by_delivery_status', 'group_name' => 'orders', 'label' => 'cbax-analytics.view.ordersByDeliveryStatus.titleTree', 'route_name' => 'cbax.analytics.getOrdersByDeliveryStatus', 'path_info' => '/cbax/analytics/getOrdersByDeliveryStatus', 'position' => 34, 'active' => 1, 'parameter' => ''),
            array('name' => 'quick_overview', 'group_name' => '', 'label' => 'cbax-analytics.view.quickOverview.titleTree', 'route_name' => 'cbax.analytics.getQuickOverview', 'path_info' => '/cbax/analytics/getQuickOverview', 'position' => 35, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished_orders', 'group_name' => 'unfinished', 'label' => 'cbax-analytics.view.unfinishedOrders.titleTree', 'route_name' => 'cbax.analytics.getUnfinishedOrders', 'path_info' => '/cbax/analytics/getUnfinishedOrders', 'position' => 36, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished_orders_by_payment', 'group_name' => 'unfinished', 'label' => 'cbax-analytics.view.unfinishedOrdersByPayment.titleTree', 'route_name' => 'cbax.analytics.getUnfinishedOrdersByPayment', 'path_info' => '/cbax/analytics/getUnfinishedOrdersByPayment', 'position' => 37, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished_orders_by_cart', 'group_name' => 'unfinished', 'label' => 'cbax-analytics.view.unfinishedOrdersByCart.titleTree', 'route_name' => 'cbax.analytics.getUnfinishedOrdersByCart', 'path_info' => '/cbax/analytics/getUnfinishedOrdersByCart', 'position' => 38, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished_orders_by_product', 'group_name' => 'unfinished', 'label' => 'cbax-analytics.view.unfinishedOrdersByProduct.titleTree', 'route_name' => 'cbax.analytics.getUnfinishedOrdersByProduct', 'path_info' => '/cbax/analytics/getUnfinishedOrdersByProduct', 'position' => 39, 'active' => 0, 'parameter' => ''),
            array('name' => 'canceled_orders_by_month', 'group_name' => 'orders', 'label' => 'cbax-analytics.view.canceledOrdersByMonth.titleTree', 'route_name' => 'cbax.analytics.getCanceledOrdersByMonth', 'path_info' => '/cbax/analytics/getCanceledOrdersByMonth', 'position' => 40, 'active' => 0, 'parameter' => '')
        );

        foreach($configFields as $field)
        {
            $sql = "SELECT `id` FROM `cbax_analytics_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $connection->executeStatement('
                INSERT IGNORE INTO `cbax_analytics_config`
                    (`id`, `name`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)',
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

        $connection->executeStatement("UPDATE `cbax_analytics_config` as c SET c.group_id=(SELECT g.id FROM `cbax_analytics_groups_config` as g WHERE g.name=c.group_name LIMIT 1);");
    }

    public function updateDestructive(Connection $connection): void
    {

    }

    /**
     * Internal helper function to check if a database table column exist.
     *
     * @param string $tableName
     * @param string $columnName
     * @param object $connection
     *
     * @return bool
     */
    public function columnExist($tableName, $columnName, $connection)
    {
        $sql = "SHOW COLUMNS FROM " . $connection->quoteIdentifier($tableName) . " LIKE ?";

        return count($connection->executeQuery($sql, array($columnName))->fetchAllAssociative()) > 0;
    }

    /**
     * Überprüfung ob Tabelle existiert
     *
     * @return bool
     */
    public function tableExist($tableName, $connection)
    {
        $sql = "SHOW TABLES LIKE ?";
        $result = $connection->executeQuery($sql, array($tableName))->fetchOne();
        return !empty($result);
    }
}


