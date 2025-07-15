<?php declare(strict_types = 1);

namespace Cbax\ModulAnalytics\Bootstrap;

use Doctrine\DBAL\Connection;

class Database
{
    public function removeDatabaseTables(array $services): void
    {
        /** @var Connection $connection  **/
        $connection = $services['connectionService'];

        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_config`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_groups_config`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_search`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_product_impressions`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_category_impressions`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_visitors`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_pool`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_referer`');
        $connection->executeStatement('DROP TABLE IF EXISTS `cbax_analytics_manufacturer_impressions`');
    }

    /**
     * Internal helper function to check if a database table column exist
     */
    public function columnExist(string $tableName, string $columnName, Connection $connection): bool
    {
        $sql = "SHOW COLUMNS FROM " . $connection->quoteIdentifier($tableName) . " LIKE ?";

        return !empty($connection->fetchAllAssociative($sql, [$columnName]));
    }

    /**
     * Überprüfung ob Tabelle existiert
     */
    public static function tableExist(string $tableName, Connection $connection): bool
    {
        $sql = "SHOW TABLES LIKE ?";
        $result = $connection->fetchOne($sql, [$tableName]);
        return !empty($result);
    }
}
