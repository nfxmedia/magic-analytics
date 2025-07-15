<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PoolEntity extends Entity
{
    use EntityIdTrait;

    use VisitorStatisticsTrait;

    /**
     * @var string|null
     */
    protected $remoteAddress;

    /**
     * @return string|null
     */
    public function getRemoteAddress(): ?string
    {
        return $this->remoteAddress;
    }

    /**
     * @param string|null $remoteAddress
     */
    public function setRemoteAddress(?string $remoteAddress): void
    {
        $this->remoteAddress = $remoteAddress;
    }

}

