<?php declare(strict_types = 1);

namespace Nfx\MagicAnalytics\Components\Statistics;

use Shopware\Core\Framework\Context;

interface StatisticsInterface
{
    public function getStatisticsData(array $parameters, Context $context): array;
}
