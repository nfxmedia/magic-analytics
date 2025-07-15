<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

class Migration1731486578Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1731486578;
    }

    public function update(Connection $connection): void
    {
        //Neue Gruppe
        $configGroupFields = [
            ['name' => 'pwreturn', 'label' => 'nfx-analytics.view.groups.pwreturn', 'position' => 10, 'active' => 1, 'parameter' => '']
        ];

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($configGroupFields as $field) {

            $sql = "SELECT `id` FROM `nfx_analytics_groups_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $groupId = Uuid::randomBytes();

            $connection->executeStatement('
                INSERT IGNORE INTO `nfx_analytics_groups_config`
                    (`id`, `name`, `label`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :label, :position, :active, :parameter, :created_at)',
                [
                    'id' => $groupId,
                    'name' => $field['name'],
                    'label' => $field['label'],
                    'position' => $field['position'],
                    'active' => $field['active'],
                    'parameter' => $field['parameter'],
                    'created_at' => $created_at
                ]
            );
        }

        //neue Statistiken
        $configFields = [
            [
                'name' => 'sales_by_products_pwreturn',
                'group_name' => 'pwreturn',
                'group_id' => $groupId,
                'label' => 'nfx-analytics.view.salesByProductsPwreturn.titleTree',
                'route_name' => 'nfx.analytics.getSalesByProductsPwreturn',
                'path_info' => '/nfx/analytics/getSalesByProductsPwreturn',
                'position' => 1,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 60}, "componentName": "nfx-analytics-index-sales-by-products-pwreturn"}'
            ],
            [
                'name' => 'pickware_returns',
                'group_name' => 'pwreturn',
                'group_id' => $groupId,
                'label' => 'nfx-analytics.view.pickwareReturns.titleTree',
                'route_name' => 'nfx.analytics.getPickwareReturns',
                'path_info' => '/nfx/analytics/getPickwareReturns',
                'position' => 2,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 0, "showTable": 0, "showChart": 0, "position": 60}, "componentName": "nfx-analytics-index-pickware-returns"}'
            ],
            [
                'name' => 'sales_all_pwreturn',
                'group_name' => 'pwreturn',
                'group_id' => $groupId,
                'label' => 'nfx-analytics.view.salesAllPwreturn.titleTree',
                'route_name' => 'nfx.analytics.getSalesAllPwreturn',
                'path_info' => '/nfx/analytics/getSalesAllPwreturn',
                'position' => 3,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 60}, "componentName": "nfx-analytics-index-sales-all-pwreturn"}'
            ],
            [
                'name' => 'sales_by_month_pwreturn',
                'group_name' => 'pwreturn',
                'group_id' => $groupId,
                'label' => 'nfx-analytics.view.salesByMonthPwreturn.titleTree',
                'route_name' => 'nfx.analytics.getSalesByMonthPwreturn',
                'path_info' => '/nfx/analytics/getSalesByMonthPwreturn',
                'position' => 4,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 60}, "componentName": "nfx-analytics-index-sales-by-month-pwreturn"}'
            ],
            [
                'name' => 'sales_by_quarter_pwreturn',
                'group_name' => 'pwreturn',
                'group_id' => $groupId,
                'label' => 'nfx-analytics.view.salesByQuarterPwreturn.titleTree',
                'route_name' => 'nfx.analytics.getSalesByQuarterPwreturn',
                'path_info' => '/nfx/analytics/getSalesByQuarterPwreturn',
                'position' => 5,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 4}, "componentName": "nfx-analytics-index-sales-by-quarter-pwreturn"}'
            ],
            [
                'name' => 'sales_all_pwreturn_invoice',
                'group_name' => 'pwreturn',
                'group_id' => $groupId,
                'label' => 'nfx-analytics.view.salesAllPwreturnInvoice.titleTree',
                'route_name' => 'nfx.analytics.getSalesAllPwreturnInvoice',
                'path_info' => '/nfx/analytics/getSalesAllPwreturnInvoice',
                'position' => 6,
                'active' => 0,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 60}, "componentName": "nfx-analytics-index-sales-all-pwreturn-invoice"}'
            ],
            [
                'name' => 'sales_by_month_pwreturn_invoice',
                'group_name' => 'pwreturn',
                'group_id' => $groupId,
                'label' => 'nfx-analytics.view.salesByMonthPwreturnInvoice.titleTree',
                'route_name' => 'nfx.analytics.getSalesByMonthPwreturnInvoice',
                'path_info' => '/nfx/analytics/getSalesByMonthPwreturnInvoice',
                'position' => 7,
                'active' => 0,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 60}, "componentName": "nfx-analytics-index-sales-by-month-pwreturn-invoice"}'
            ],
            [
                'name' => 'sales_by_quarter_pwreturn_invoice',
                'group_name' => 'pwreturn',
                'group_id' => $groupId,
                'label' => 'nfx-analytics.view.salesByQuarterPwreturnInvoice.titleTree',
                'route_name' => 'nfx.analytics.getSalesByQuarterPwreturnInvoice',
                'path_info' => '/nfx/analytics/getSalesByQuarterPwreturnInvoice',
                'position' => 8,
                'active' => 0,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 4}, "componentName": "nfx-analytics-index-sales-by-quarter-pwreturn-invoice"}'
            ]
        ];

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($configFields as $field) {

            $sql = "SELECT `id` FROM `nfx_analytics_config` WHERE `name` = ?;";
            $found = $connection->fetchOne($sql, [$field['name']]);

            if (!empty($found)) {
                continue;
            }

            $connection->executeStatement('
                INSERT IGNORE INTO `nfx_analytics_config`
                    (`id`, `name`, `group_id`, `group_name`, `label`, `route_name`, `path_info`,`position`, `active`, `parameter`, `created_at`)
                VALUES
                    (:id, :name, :group_id, :group_name, :label, :route_name, :path_info, :position, :active, :parameter, :created_at)',
                [
                    'id' => Uuid::randomBytes(),
                    'name' => $field['name'],
                    'group_name' => $field['group_name'],
                    'group_id' => $field['group_id'],
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

