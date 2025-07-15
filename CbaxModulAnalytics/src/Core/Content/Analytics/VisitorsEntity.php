<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class VisitorsEntity extends Entity
{
    use EntityIdTrait;

    use VisitorStatisticsTrait;

    /**
     * @var int|null
     */
    protected $pageImpressions;

    /**
     * @var int|null
     */
    protected $uniqueVisits;

    /**
     * @var string|null
     */
    protected $deviceType;

    /**
     * @return int|null
     */
    public function getPageImpressions(): ?int
    {
        return $this->pageImpressions;
    }

    /**
     * @param int|null $pageImpressions
     */
    public function setPageImpressions(?int $pageImpressions): void
    {
        $this->pageImpressions = $pageImpressions;
    }

    /**
     * @return int|null
     */
    public function getUniqueVisits(): ?int
    {
        return $this->uniqueVisits;
    }

    /**
     * @param int|null $uniqueVisits
     */
    public function setUniqueVisits(?int $uniqueVisits): void
    {
        $this->uniqueVisits = $uniqueVisits;
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

