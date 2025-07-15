<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Migration;

use Doctrine\DBAL\Connection;
//use Shopware\Core\Defaults;
//use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1614589470Analytics extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1614589470;
    }

    public function update(Connection $connection): void
    {
        if ($this->tableExist('cbax_analytics_config', $connection))
        {
            if (!$this->columnExist('cbax_analytics_config', 'plugin', $connection))
            {
                $connection->executeStatement("ALTER TABLE `cbax_analytics_config` ADD `plugin` varchar(255) DEFAULT NULL AFTER `active`;");
            }
        }

        if ($this->tableExist('cbax_analytics_groups_config', $connection))
        {
            if (!$this->columnExist('cbax_analytics_groups_config', 'plugin', $connection))
            {
                $connection->executeStatement("ALTER TABLE `cbax_analytics_groups_config` ADD `plugin` varchar(255) DEFAULT NULL AFTER `active`;");
            }
        }
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



