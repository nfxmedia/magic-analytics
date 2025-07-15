<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void        add(GroupEntity $entity)
 * @method void        set(string $key, GroupEntity $entity)
 * @method GroupEntity[]    getIterator()
 * @method GroupEntity[]    getElements()
 * @method GroupEntity|null get(string $key)
 * @method GroupEntity|null first()
 * @method GroupEntity|null last()
 */
class GroupCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ConfigEntity::class;
    }
}

