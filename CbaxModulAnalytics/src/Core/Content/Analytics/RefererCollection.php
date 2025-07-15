<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void        add(RefererEntity $entity)
 * @method void        set(string $key, RefererEntity $entity)
 * @method RefererEntity[]    getIterator()
 * @method RefererEntity[]    getElements()
 * @method RefererEntity|null get(string $key)
 * @method RefererEntity|null first()
 * @method RefererEntity|null last()
 */
class RefererCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return RefererEntity::class;
    }
}
