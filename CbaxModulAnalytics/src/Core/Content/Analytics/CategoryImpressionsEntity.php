<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class CategoryImpressionsEntity extends Entity
{
    use EntityIdTrait;

    use ImpressionStatisticsTrait;

    /**
     * @var string|null
     */
    protected $categoryId;

    /**
     * @return string|null
     */
    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    /**
     * @param string|null $categoryId
     */
    public function setCategoryId(?string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }
}
