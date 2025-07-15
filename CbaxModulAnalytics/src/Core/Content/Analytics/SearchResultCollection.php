<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void        add(SearchResultEntity $entity)
 * @method void        set(string $key, SearchResultEntity $entity)
 * @method SearchResultEntity[]    getIterator()
 * @method SearchResultEntity[]    getElements()
 * @method SearchResultEntity|null get(string $key)
 * @method SearchResultEntity|null first()
 * @method SearchResultEntity|null last()
 */
class SearchResultCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SearchResultEntity::class;
    }
}
