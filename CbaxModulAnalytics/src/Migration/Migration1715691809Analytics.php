<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;
use Cbax\ModulAnalytics\Bootstrap\Database;

class Migration1715691809Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1715691809;
    }

    public function update(Connection $connection): void
    {
        //Constraint kann errors verursachen
        try {
            if (Database::tableExist('cbax_analytics_invoice_date', $connection)) {
                $connection->executeStatement("ALTER TABLE `cbax_analytics_invoice_date` DROP FOREIGN KEY `fk.cbax_analytics_invoice_date.order_id`;");
            }
        } catch (\Exception) {
        }

        //Statistiken ändern
        $toChange = [
            [
                //Kategorie Statistik aktivieren, soll im Dashboard nich auswählbar sein, da diese die Wahl der kategorie erfordert.
                'sql' => "UPDATE `cbax_analytics_config`
                           SET `active` = 1, `parameter` = JSON_SET(`parameter`, '$.dashboard.hasTable', 0, '$.dashboard.hasChart', 0)
                           WHERE `name` = ? AND `active` = 0;",
                'params' => ['sales_by_category'],
                'types' => []
            ]
        ];
        foreach ($toChange as $change) {
            $connection->executeStatement($change['sql'], $change['params'],$change['types']);
        }

        //neue Statistiken
        $configFields = [
            [
                'name' => 'category_compare',
                'group_name' => 'sales',
                'label' => 'cbax-analytics.view.categoryCompare.titleTree',
                'route_name' => 'cbax.analytics.getCategoryCompare',
                'path_info' => '/cbax/analytics/getCategoryCompare',
                'position' => 19,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 0, "hasChart": 0, "showTable": 0, "showChart": 0, "position": 50}, "componentName": "cbax-analytics-index-category-compare"}'
            ],
            [
                'name' => 'sales_by_quarter',
                'group_name' => 'orders',
                'label' => 'cbax-analytics.view.salesByQuarter.titleTree',
                'route_name' => 'cbax.analytics.getSalesByQuarter',
                'path_info' => '/cbax/analytics/getSalesByQuarter',
                'position' => 4,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 4}, "componentName": "cbax-analytics-index-sales-by-quarter"}'
            ]
        ];

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($configFields as $field) {

            $sql = "SELECT `id` FROM `cbax_analytics_config` WHERE `name` = ?;";
            $found = $connection->fetchOne($sql, [$field['name']]);

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

            $connection->executeStatement(
                "UPDATE `cbax_analytics_config` as c
                SET c.group_id=(SELECT g.id FROM `cbax_analytics_groups_config` as g
                WHERE g.name=? LIMIT 1) WHERE c.name=?;",
                [$field['group_name'], $field['name']]
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {

    }

}


