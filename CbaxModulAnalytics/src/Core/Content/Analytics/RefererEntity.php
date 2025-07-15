<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class RefererEntity extends Entity
{
    use EntityIdTrait;

    use VisitorStatisticsTrait;

    /**
     * @var string|null
     */
    protected $referer;

    /**
     * @var int|null
     */
    protected $counted;

    /**
     * @var string|null
     */
    protected $deviceType;

    /**
     * @return string|null
     */
    public function getReferer(): ?string
    {
        return $this->referer;
    }

    /**
     * @param string|null $referer
     */
    public function setReferer(?string $referer): void
    {
        $this->referer = $referer;
    }

    /**
     * @return int|null
     */
    public function getCounted(): ?int
    {
        return $this->counted;
    }

    /**
     * @param int|null $counted
     */
    public function setCounted(?int $counted): void
    {
        $this->counted = $counted;
    }

    /**
     * @return string|null
     */
    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    /**
     * @param string|null $deviceType
     */
    public function setDeviceType(?string $deviceType): void
    {
        $this->deviceType = $deviceType;
    }


}

