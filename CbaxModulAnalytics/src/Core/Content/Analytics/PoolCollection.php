<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void        add(PoolEntity $entity)
 * @method void        set(string $key, PoolEntity $entity)
 * @method PoolEntity[]    getIterator()
 * @method PoolEntity[]    getElements()
 * @method PoolEntity|null get(string $key)
 * @method PoolEntity|null first()
 * @method PoolEntity|null last()
 */
class PoolCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PoolEntity::class;
    }
}