<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Defaults;

class Migration1659962090Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1659962090;
    }

    public function update(Connection $connection): void
    {
        $configFields = array (
            array(
                'name' => 'single_product',
                'group_name' => 'products',
                'label' => 'cbax-analytics.view.singleProduct.titleTree',
                'route_name' => 'cbax.analytics.getSingleProduct',
                'path_info' => '/cbax/analytics/getSingleProduct',
                'position' => 50,
                'active' => 1,
                'parameter' => '{"dashboard": {"hasTable": 0, "hasChart": 0, "showTable": 0, "showChart": 0, "position": 50}, "componentName": "cbax-analytics-index-single-product"}'),
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
            ('single_product');
        ");

        $connection->executeStatement("UPDATE `cbax_analytics_config` as c
            SET c.group_name = 'visitors', c.active = 1, c.position = 50, c.parameter = JSON_SET(`parameter`, '$.dashboard.hasTable', 1, '$.dashboard.position', 50, '$.dashboard.hasChart', 1, '$.componentName', 'cbax-analytics-index-lexicon-impressions'), c.group_id=(SELECT g.id FROM `cbax_analytics_groups_config` as g
            WHERE g.name='visitors' LIMIT 1) WHERE c.name IN
            ('lexicon_impressions');
        ");
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}


