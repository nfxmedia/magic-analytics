<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1698841393Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1698841393;
    }

    public function update(Connection $connection): void
    {
        $sql = "
                ALTER TABLE `nfx_analytics_search` DROP FOREIGN KEY `fk.nfx_analytics_search.sales_channel_id`;
                ALTER TABLE `nfx_analytics_search` ADD CONSTRAINT `fk.nfx_analytics_search.sales_channel_id` FOREIGN KEY (`sales_channel_id`)
                 REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
                ";
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {

    }
}
