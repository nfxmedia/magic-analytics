<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Extension\Checkout\Order;

use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;

class AnalyticsOrderExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToOneAssociationField('invoiceDate', 'id', 'order_id', InvoiceDateExtensionDefinition::class, false)
        );
    }

    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }

}


