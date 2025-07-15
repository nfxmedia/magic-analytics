<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class Analytics extends ScheduledTask

{
    public static function getTaskName(): string
    {
        return 'nfx.analytics_clean_up';
    }

    public static function getDefaultInterval(): int
    {
        return 259200;
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return true;
    }
}
