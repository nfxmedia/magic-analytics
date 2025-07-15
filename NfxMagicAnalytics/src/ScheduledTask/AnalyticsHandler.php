<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\ScheduledTask;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SystemConfig\SystemConfigService;

#[AsMessageHandler(handles: Analytics::class)]
class AnalyticsHandler extends ScheduledTaskHandler
{
    const CONFIG_PATH = 'NfxModulAnalytics.config';
    private ?array $config = null;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService
        )
    {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $this->config = $this->config ?? $this->systemConfigService->get(self::CONFIG_PATH) ?? [];

        $deleteSearchTime = !empty($this->config['deleteSearchTime']) ? $this->config['deleteSearchTime'] : 180;
        if ($deleteSearchTime != 1) {
            $sql = "DELETE FROM `nfx_analytics_search` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
            $this->connection->executeStatement($sql, [$deleteSearchTime]);
        }

        $sql = "DELETE FROM `nfx_analytics_pool` WHERE DATEDIFF(NOW(), `date`) > ?;";
        $this->connection->executeStatement($sql, [2]);

        //Visitor-Tabellen leeren
        $deleteVisitorsTime = !empty($this->config['deleteVisitorsTime']) ? $this->config['deleteVisitorsTime'] : 180;
        if ($deleteVisitorsTime != 1) {
            $sql = "DELETE FROM `nfx_analytics_visitors` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
            $this->connection->executeStatement($sql, [$deleteVisitorsTime]);

            $sql = "DELETE FROM `nfx_analytics_referer` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
            $this->connection->executeStatement($sql, [$deleteVisitorsTime]);

            $sql = "DELETE FROM `nfx_analytics_product_impressions` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
            $this->connection->executeStatement($sql, [$deleteVisitorsTime]);

            $sql = "DELETE FROM `nfx_analytics_category_impressions` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
            $this->connection->executeStatement($sql, [$deleteVisitorsTime]);

            $sql = "DELETE FROM `nfx_analytics_manufacturer_impressions` WHERE DATEDIFF(NOW(), `created_at`) > ?;";
            $this->connection->executeStatement($sql, [$deleteVisitorsTime]);
        }

    }
}
