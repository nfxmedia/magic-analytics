<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void        add(ProductImpressionsEntity $entity)
 * @method void        set(string $key, ProductImpressionsEntity $entity)
 * @method ProductImpressionsEntity[]    getIterator()
 * @method ProductImpressionsEntity[]    getElements()
 * @method ProductImpressionsEntity|null get(string $key)
 * @method ProductImpressionsEntity|null first()
 * @method ProductImpressionsEntity|null last()
 */
class ProductImpressionsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductImpressionsEntity::class;
    }
}
