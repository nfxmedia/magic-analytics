<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void        add(ConfigEntity $entity)
 * @method void        set(string $key, ConfigEntity $entity)
 * @method ConfigEntity[]    getIterator()
 * @method ConfigEntity[]    getElements()
 * @method ConfigEntity|null get(string $key)
 * @method ConfigEntity|null first()
 * @method ConfigEntity|null last()
 */
class ConfigCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ConfigEntity::class;
    }
}
