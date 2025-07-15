<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void        add(VisitorsEntity $entity)
 * @method void        set(string $key, VisitorsEntity $entity)
 * @method VisitorsEntity[]    getIterator()
 * @method VisitorsEntity[]    getElements()
 * @method VisitorsEntity|null get(string $key)
 * @method VisitorsEntity|null first()
 * @method VisitorsEntity|null last()
 */
class VisitorsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return VisitorsEntity::class;
    }
}
