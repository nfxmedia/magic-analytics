<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1572516630Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1572516630;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('DROP TABLE IF EXISTS `nfx_analytics_config`');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `nfx_analytics_groups_config` (
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
            array('name' => 'sales', 'label' => 'nfx-analytics.view.groups.sales', 'position' => 1, 'active' => 1, 'parameter' => ''),
            array('name' => 'customers', 'label' => 'nfx-analytics.view.groups.customers', 'position' => 5, 'active' => 1, 'parameter' => ''),
            array('name' => 'products', 'label' => 'nfx-analytics.view.groups.products', 'position' => 4, 'active' => 1, 'parameter' => ''),
            array('name' => 'marketing', 'label' => 'nfx-analytics.view.groups.marketing', 'position' => 3, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders', 'label' => 'nfx-analytics.view.groups.orders', 'position' => 2, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished', 'label' => 'nfx-analytics.view.groups.unfinished', 'position' => 6, 'active' => 1, 'parameter' => ''),
            array('name' => 'lexicon', 'label' => 'nfx-analytics.view.groups.lexicon', 'position' => 7, 'active' => 0, 'parameter' => ''),
            array('name' => 'others', 'label' => 'nfx-analytics.view.groups.others', 'position' => 8, 'active' => 0, 'parameter' => '')
        );

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $groupIds = [];

        foreach($configGroupFields as $field) {

            $sql = "SELECT `id` FROM `nfx_analytics_groups_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $randomId = Uuid::randomBytes();
            $groupIds[$field['name']] = $randomId;

            $connection->executeStatement('
                INSERT IGNORE INTO `nfx_analytics_groups_config`
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

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `nfx_analytics_config` (
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
                            CONSTRAINT `fk.nfx_analytics_config.group_id` FOREIGN KEY (`group_id`)
                            REFERENCES `nfx_analytics_groups_config` (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $configFields = array (
            array('name' => 'sales_all', 'group_name' => 'orders', 'label' => 'nfx-analytics.view.orderSales.titleTree', 'route_name' => 'nfx.analytics.getSalesAll', 'path_info' => '/nfx/analytics/getSalesAll', 'position' => 2, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders_count_all', 'group_name' => 'orders', 'label' => 'nfx-analytics.view.orderCountAll.titleTree', 'route_name' => 'nfx.analytics.getOrdersCountAll', 'path_info' => '/nfx/analytics/getOrdersCountAll', 'position' => 1, 'active' => 0, 'parameter' => ''),
            array('name' => 'sales_by_manufacturer', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesByManufacturer.titleTree', 'route_name' => 'nfx.analytics.getSalesByManufacturer', 'path_info' => '/nfx/analytics/getSalesByManufacturer', 'position' => 7, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_month', 'group_name' => 'orders', 'label' => 'nfx-analytics.view.orderSalesMonthly.titleTree', 'route_name' => 'nfx.analytics.getSalesByMonth', 'path_info' => '/nfx/analytics/getSalesByMonth', 'position' => 3, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_shipping_method', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesByShippingMethod.titleTree', 'route_name' => 'nfx.analytics.getSalesByShipping', 'path_info' => '/nfx/analytics/getSalesByShipping', 'position' => 8, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_payment_method', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesByPaymentMethod.titleTree', 'route_name' => 'nfx.analytics.getSalesByPayment', 'path_info' => '/nfx/analytics/getSalesByPayment', 'position' => 9, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_country', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesByCountry.titleTree', 'route_name' => 'nfx.analytics.getSalesByCountry', 'path_info' => '/nfx/analytics/getSalesByCountry', 'position' => 10, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_language', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesByLanguage.titleTree', 'route_name' => 'nfx.analytics.getSalesByLanguage', 'path_info' => '/nfx/analytics/getSalesByLanguage', 'position' => 11, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_products', 'group_name' => 'products', 'label' => 'nfx-analytics.view.salesByProducts.titleTree', 'route_name' => 'nfx.analytics.getSalesByProducts', 'path_info' => '/nfx/analytics/getSalesByProducts', 'position' => 16, 'active' => 1, 'parameter' => ''),
            array('name' => 'count_by_products', 'group_name' => 'products', 'label' => 'nfx-analytics.view.countByProducts.titleTree', 'route_name' => 'nfx.analytics.getCountByProducts', 'path_info' => '/nfx/analytics/getCountByProducts', 'position' => 17, 'active' => 0, 'parameter' => ''),
            array('name' => 'sales_by_saleschannel', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesBySaleschannel.titleTree', 'route_name' => 'nfx.analytics.getSalesBySaleschannels', 'path_info' => '/nfx/analytics/getSalesBySaleschannels', 'position' => 12, 'active' => 1, 'parameter' => ''),
            array('name' => 'customer_age', 'group_name' => 'customers', 'label' => 'nfx-analytics.view.customerAge.titleTree', 'route_name' => 'nfx.analytics.getCustomerAge', 'path_info' => '/nfx/analytics/getCustomerAge', 'position' => 14, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_affiliate', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesByAffiliate.titleTree', 'route_name' => 'nfx.analytics.getSalesByAffiliates', 'path_info' => '/nfx/analytics/getSalesByAffiliates', 'position' => 6, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_campaign', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesByCampaign.titleTree', 'route_name' => 'nfx.analytics.getSalesByCampaign', 'path_info' => '/nfx/analytics/getSalesByCampaign', 'position' => 15, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_weekdays', 'group_name' => 'orders', 'label' => 'nfx-analytics.view.salesByWeekdays.titleTree', 'route_name' => 'nfx.analytics.getSalesByWeekdays', 'path_info' => '/nfx/analytics/getSalesByWeekdays', 'position' => 4, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_time', 'group_name' => 'orders', 'label' => 'nfx-analytics.view.salesByTime.titleTree', 'route_name' => 'nfx.analytics.getSalesByTime', 'path_info' => '/nfx/analytics/getSalesByTime', 'position' => 5, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_customergroups', 'group_name' => 'customers', 'label' => 'nfx-analytics.view.salesByCustomergroups.titleTree', 'route_name' => 'nfx.analytics.getSalesByCustomergroups', 'path_info' => '/nfx/analytics/getSalesByCustomergroups', 'position' => 13, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_category', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesByCategory.titleTree', 'route_name' => 'nfx.analytics.getSalesByCategory', 'path_info' => '/nfx/analytics/getSalesByCategory', 'position' => 18, 'active' => 0, 'parameter' => ''),
            array('name' => 'lexicon_impressions', 'group_name' => 'lexicon', 'label' => 'nfx-analytics.view.lexiconImpressions.titleTree', 'route_name' => 'nfx.analytics.getLexiconImpressions', 'path_info' => '/nfx/analytics/getLexiconImpressions', 'position' => 19, 'active' => 0, 'parameter' => ''),
            array('name' => 'sales_by_device', 'group_name' => 'sales', 'label' => 'nfx-analytics.view.salesByDevice.titleTree', 'route_name' => 'nfx.analytics.getSalesByDevice', 'path_info' => '/nfx/analytics/getSalesByDevice', 'position' => 20, 'active' => 0, 'parameter' => ''),
            array('name' => 'sales_by_promotion', 'group_name' => 'marketing', 'label' => 'nfx-analytics.view.salesByPromotion.titleTree', 'route_name' => 'nfx.analytics.getSalesByPromotion', 'path_info' => '/nfx/analytics/getSalesByPromotion', 'position' => 21, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_promotion_others', 'group_name' => 'marketing', 'label' => 'nfx-analytics.view.salesByPromotionOthers.titleTree', 'route_name' => 'nfx.analytics.getSalesByPromotionOthers', 'path_info' => '/nfx/analytics/getSalesByPromotionOthers', 'position' => 29, 'active' => 1, 'parameter' => ''),
            array('name' => 'search_terms', 'group_name' => 'marketing', 'label' => 'nfx-analytics.view.searchTerms.titleTree', 'route_name' => 'nfx.analytics.getSearchTerms', 'path_info' => '/nfx/analytics/getSearchTerms', 'position' => 22, 'active' => 0, 'parameter' => ''),
            array('name' => 'product_impressions', 'group_name' => 'products', 'label' => 'nfx-analytics.view.productImpressions.titleTree', 'route_name' => 'nfx.analytics.getProductImpressions', 'path_info' => '/nfx/analytics/getProductImpressions', 'position' => 23, 'active' => 0, 'parameter' => ''),
            array('name' => 'product_low_instock', 'group_name' => 'products', 'label' => 'nfx-analytics.view.productLowInstock.titleTree', 'route_name' => 'nfx.analytics.getProductLowInstock', 'path_info' => '/nfx/analytics/getProductLowInstock', 'position' => 24, 'active' => 1, 'parameter' => ''),
            array('name' => 'product_inactive_with_instock', 'group_name' => 'products', 'label' => 'nfx-analytics.view.productInactiveWithInstock.titleTree', 'route_name' => 'nfx.analytics.getProductInactiveWithInstock', 'path_info' => '/nfx/analytics/getProductInactiveWithInstock', 'position' => 25, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders_by_status', 'group_name' => 'orders', 'label' => 'nfx-analytics.view.ordersByStatus.titleTree', 'route_name' => 'nfx.analytics.getOrdersByStatus', 'path_info' => '/nfx/analytics/getOrdersByStatus', 'position' => 26, 'active' => 1, 'parameter' => ''),
            array('name' => 'product_high_instock', 'group_name' => 'products', 'label' => 'nfx-analytics.view.productHighInstock.titleTree', 'route_name' => 'nfx.analytics.getProductHighInstock', 'path_info' => '/nfx/analytics/getProductHighInstock', 'position' => 27, 'active' => 1, 'parameter' => ''),
            array('name' => 'product_soon_outstock', 'group_name' => 'products', 'label' => 'nfx-analytics.view.productSoonOutstock.titleTree', 'route_name' => 'nfx.analytics.getProductSoonOutstock', 'path_info' => '/nfx/analytics/getProductSoonOutstock', 'position' => 28, 'active' => 1, 'parameter' => ''),
            array('name' => 'product_by_orders', 'group_name' => 'products', 'label' => 'nfx-analytics.view.productByOrders.titleTree', 'route_name' => 'nfx.analytics.getProductByOrders', 'path_info' => '/nfx/analytics/getProductByOrders', 'position' => 30, 'active' => 1, 'parameter' => ''),
            array('name' => 'sales_by_customer', 'group_name' => 'customers', 'label' => 'nfx-analytics.view.salesByCustomer.titleTree', 'route_name' => 'nfx.analytics.getSalesByCustomer', 'path_info' => '/nfx/analytics/getSalesByCustomer', 'position' => 31, 'active' => 1, 'parameter' => ''),
            array('name' => 'new_customers_by_time', 'group_name' => 'customers', 'label' => 'nfx-analytics.view.newCustomersByTime.titleTree', 'route_name' => 'nfx.analytics.getNewCustomersByTime', 'path_info' => '/nfx/analytics/getNewCustomersByTime', 'position' => 32, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders_by_transaction_status', 'group_name' => 'orders', 'label' => 'nfx-analytics.view.ordersByTransactionStatus.titleTree', 'route_name' => 'nfx.analytics.getOrdersByTransactionStatus', 'path_info' => '/nfx/analytics/getOrdersByTransactionStatus', 'position' => 33, 'active' => 1, 'parameter' => ''),
            array('name' => 'orders_by_delivery_status', 'group_name' => 'orders', 'label' => 'nfx-analytics.view.ordersByDeliveryStatus.titleTree', 'route_name' => 'nfx.analytics.getOrdersByDeliveryStatus', 'path_info' => '/nfx/analytics/getOrdersByDeliveryStatus', 'position' => 34, 'active' => 1, 'parameter' => ''),
            array('name' => 'quick_overview', 'group_name' => '', 'label' => 'nfx-analytics.view.quickOverview.titleTree', 'route_name' => 'nfx.analytics.getQuickOverview', 'path_info' => '/nfx/analytics/getQuickOverview', 'position' => 35, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished_orders', 'group_name' => 'unfinished', 'label' => 'nfx-analytics.view.unfinishedOrders.titleTree', 'route_name' => 'nfx.analytics.getUnfinishedOrders', 'path_info' => '/nfx/analytics/getUnfinishedOrders', 'position' => 36, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished_orders_by_payment', 'group_name' => 'unfinished', 'label' => 'nfx-analytics.view.unfinishedOrdersByPayment.titleTree', 'route_name' => 'nfx.analytics.getUnfinishedOrdersByPayment', 'path_info' => '/nfx/analytics/getUnfinishedOrdersByPayment', 'position' => 37, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished_orders_by_cart', 'group_name' => 'unfinished', 'label' => 'nfx-analytics.view.unfinishedOrdersByCart.titleTree', 'route_name' => 'nfx.analytics.getUnfinishedOrdersByCart', 'path_info' => '/nfx/analytics/getUnfinishedOrdersByCart', 'position' => 38, 'active' => 1, 'parameter' => ''),
            array('name' => 'unfinished_orders_by_product', 'group_name' => 'unfinished', 'label' => 'nfx-analytics.view.unfinishedOrdersByProduct.titleTree', 'route_name' => 'nfx.analytics.getUnfinishedOrdersByProduct', 'path_info' => '/nfx/analytics/getUnfinishedOrdersByProduct', 'position' => 39, 'active' => 0, 'parameter' => ''),
            array('name' => 'canceled_orders_by_month', 'group_name' => 'orders', 'label' => 'nfx-analytics.view.canceledOrdersByMonth.titleTree', 'route_name' => 'nfx.analytics.getCanceledOrdersByMonth', 'path_info' => '/nfx/analytics/getCanceledOrdersByMonth', 'position' => 40, 'active' => 0, 'parameter' => '')
        );

        foreach($configFields as $field) {

            $sql = "SELECT `id` FROM `nfx_analytics_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $groupId = !empty($groupIds[$field['group_name']]) ? $groupIds[$field['group_name']] : NULL;

            $connection->executeStatement('
                INSERT IGNORE INTO `nfx_analytics_config`
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
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
