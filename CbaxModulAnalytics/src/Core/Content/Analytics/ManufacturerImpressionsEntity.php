<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ManufacturerImpressionsEntity extends Entity
{
    use EntityIdTrait;

    use ImpressionStatisticsTrait;

    /**
     * @var string|null
     */
    protected $manufacturerId;

    /**
     * @return string|null
     */
    public function getManufacturerId(): ?string
    {
        return $this->manufacturerId;
    }

    /**
     * @param string|null $manufacturerId
     */
    public function setManufacturerId(?string $manufacturerId): void
    {
        $this->manufacturerId = $manufacturerId;
    }
}
