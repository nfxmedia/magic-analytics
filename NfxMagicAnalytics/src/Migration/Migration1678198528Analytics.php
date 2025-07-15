<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

class Migration1678198528Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1678198528;
    }

    public function update(Connection $connection): void
    {
        $configFields = [
            [
                'name' => 'sales_by_taxrate',
                'group_name' => 'sales',
                'label' => 'nfx-analytics.view.salesByTaxrate.titleTree',
                'route_name' => 'nfx.analytics.getSalesByTaxrate',
                'path_info' => '/nfx/analytics/getSalesByTaxrate',
                'position' => 53,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 53}, "componentName": "nfx-analytics-index-sales-by-taxrate"}'
            ],
            [
                'name' => 'sales_by_salutation',
                'group_name' => 'sales',
                'label' => 'nfx-analytics.view.salesBySalutation.titleTree',
                'route_name' => 'nfx.analytics.getSalesBySalutation',
                'path_info' => '/nfx/analytics/getSalesBySalutation',
                'position' => 54,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 54}, "componentName": "nfx-analytics-index-sales-by-salutation"}'
            ],
            [
                'name' => 'customer_by_salutation',
                'group_name' => 'customers',
                'label' => 'nfx-analytics.view.customerBySalutation.titleTree',
                'route_name' => 'nfx.analytics.getCustomerBySalutation',
                'path_info' => '/nfx/analytics/getCustomerBySalutation',
                'position' => 55,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 1, "hasChart": 1, "showTable": 0, "showChart": 0, "position": 55}, "componentName": "nfx-analytics-index-customer-by-salutation"}'
            ]
        ];

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($configFields as $field) {

            $sql = "SELECT `id` FROM `nfx_analytics_config` WHERE `name` = '" . $field['name'] . "'";
            $found = $connection->executeQuery($sql)->fetchOne();

            if (!empty($found)) {
                continue;
            }

            $connection->executeStatement('
                INSERT IGNORE INTO `nfx_analytics_config`
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

        $connection->executeStatement("UPDATE `nfx_analytics_config` as c
            SET c.group_id=(SELECT g.id FROM `nfx_analytics_groups_config` as g
            WHERE g.name='sales' LIMIT 1) WHERE c.name IN
            ('sales_by_taxrate', 'sales_by_salutation');
        ");
        $connection->executeStatement("UPDATE `nfx_analytics_config` as c
            SET c.group_id=(SELECT g.id FROM `nfx_analytics_groups_config` as g
            WHERE g.name='customers' LIMIT 1) WHERE c.name IN
            ('customer_by_salutation');
        ");
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}



