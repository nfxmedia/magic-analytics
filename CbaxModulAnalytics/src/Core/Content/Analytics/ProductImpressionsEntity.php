<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductImpressionsEntity extends Entity
{
    use EntityIdTrait;

    use ImpressionStatisticsTrait;

    /**
     * @var string|null
     */
    protected $productId;

    /**
     * @return string|null
     */
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    /**
     * @param string|null $productId
     */
    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }
}
