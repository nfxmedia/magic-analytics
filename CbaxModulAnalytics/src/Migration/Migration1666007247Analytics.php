<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

class Migration1666007247Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1666007247;
    }

    public function update(Connection $connection): void
    {
        $configFields = array (
            array(
                'name' => 'cross_selling',
                'group_name' => 'products',
                'label' => 'cbax-analytics.view.crossSelling.titleTree',
                'route_name' => 'cbax.analytics.getCrossSelling',
                'path_info' => '/cbax/analytics/getCrossSelling',
                'position' => 51,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 0, "hasChart": 0, "showTable": 0, "showChart": 0, "position": 51}, "componentName": "cbax-analytics-index-cross-selling"}'),
        );

        $created_at = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach($configFields as $field) {

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
            WHERE g.name='products' LIMIT 1) WHERE c.name IN
            ('cross_selling');
        ");
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}

