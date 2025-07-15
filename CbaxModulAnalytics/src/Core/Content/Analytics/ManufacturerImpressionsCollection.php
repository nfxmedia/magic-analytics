<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void        add(ManufacturerImpressionsEntity $entity)
 * @method void        set(string $key, ManufacturerImpressionsEntity $entity)
 * @method ManufacturerImpressionsEntity[]    getIterator()
 * @method ManufacturerImpressionsEntity[]    getElements()
 * @method ManufacturerImpressionsEntity|null get(string $key)
 * @method ManufacturerImpressionsEntity|null first()
 * @method ManufacturerImpressionsEntity|null last()
 */
class ManufacturerImpressionsCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ManufacturerImpressionsEntity::class;
    }
}
