<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void        add(CategoryImpressionsEntity $entity)
 * @method void        set(string $key, CategoryImpressionsEntity $entity)
 * @method CategoryImpressionsEntity[]    getIterator()
 * @method CategoryImpressionsEntity[]    getElements()
 * @method CategoryImpressionsEntity|null get(string $key)
 * @method CategoryImpressionsEntity|null first()
 * @method CategoryImpressionsEntity|null last()
 */
class CategoryImpressionsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CategoryImpressionsEntity::class;
    }
}
