<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Extension\Checkout\Order;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<InvoiceDateExtensionEntity>
 */
class InvoiceDateExtensionCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return InvoiceDateExtensionEntity::class;
    }

}