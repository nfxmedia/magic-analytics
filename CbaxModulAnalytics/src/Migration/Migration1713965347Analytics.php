<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

class Migration1713965347Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1713965347;
    }

    public function update(Connection $connection): void
    {
        $configGroupFields = [
            ['name' => 'invoice', 'label' => 'cbax-analytics.view.groups.invoice', 'position' => 9, 'active' => 1, 'parameter' => '']
        ];

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($configGroupFields as $field) {

            $sql = "SELECT `id` FROM `cbax_analytics_groups_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $randomId = Uuid::randomBytes();

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

        $configFields = [
            [
                'name' => 'sales_all_invoice',
                'group_name' => 'invoice',
                'label' => 'cbax-analytics.view.salesAllInvoice.titleTree',
                'route_name' => 'cbax.analytics.getSalesAllInvoice',
                'path_info' => '/cbax/analytics/getSalesAllInvoice',
                'position' => 1,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 50}, "componentName": "cbax-analytics-index-sales-all-invoice"}'
            ],
            [
                'name' => 'sales_by_month_invoice',
                'group_name' => 'invoice',
                'label' => 'cbax-analytics.view.salesByMonthInvoice.titleTree',
                'route_name' => 'cbax.analytics.getSalesByMonthInvoice',
                'path_info' => '/cbax/analytics/getSalesByMonthInvoice',
                'position' => 2,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 51}, "componentName": "cbax-analytics-index-sales-by-month-invoice"}'
            ],
            [
                'name' => 'sales_by_quarter_invoice',
                'group_name' => 'invoice',
                'label' => 'cbax-analytics.view.salesByQuarterInvoice.titleTree',
                'route_name' => 'cbax.analytics.getSalesByQuarterInvoice',
                'path_info' => '/cbax/analytics/getSalesByQuarterInvoice',
                'position' => 3,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 52}, "componentName": "cbax-analytics-index-sales-by-quarter-invoice"}'
            ],
            [
                'name' => 'sales_by_billing_country_invoice',
                'group_name' => 'invoice',
                'label' => 'cbax-analytics.view.salesByBillingCountryInvoice.titleTree',
                'route_name' => 'cbax.analytics.getSalesByBillingCountryInvoice',
                'path_info' => '/cbax/analytics/getSalesByBillingCountryInvoice',
                'position' => 4,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 53}, "componentName": "cbax-analytics-index-sales-by-billing-country-invoice"}'
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

        $connection->executeStatement("
            UPDATE `cbax_analytics_config` as c
            SET c.group_id=(SELECT g.id FROM `cbax_analytics_groups_config` as g
            WHERE g.name='invoice' LIMIT 1) WHERE c.group_name = 'invoice';
        ");

        $sql = "
            CREATE TABLE IF NOT EXISTS `cbax_analytics_invoice_date` (
                `id` BINARY(16) NOT NULL,
                `order_id` BINARY(16) NULL,
                `invoice_date_time` DATETIME(3) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`order_id`),
                KEY `fk.cbax_analytics_invoice_date.order_id` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $connection->executeStatement($sql);

        $invoiceDocumentTypeId = $connection->fetchOne("SELECT `id` FROM `document_type` WHERE `technical_name` = ?;", ['invoice']);

        if (!empty($invoiceDocumentTypeId)) {
            try {
                $sql = '
                    INSERT IGNORE INTO `cbax_analytics_invoice_date` (`id`, `order_id`, `invoice_date_time`, `created_at`)
                        (SELECT UNHEX(REPLACE(UUID(), "-", "")), d.`order_id`, JSON_UNQUOTE(JSON_EXTRACT(d.`config`, "$.documentDate")), ?
                        FROM `document` AS d WHERE d.`document_type_id` = ?);
                    ';

                $connection->executeStatement($sql, [$created_at, $invoiceDocumentTypeId]);

            } catch (\Exception) {

            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {

    }

}


