<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Extension\Checkout\Order;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class InvoiceDateExtensionDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cbax_analytics_invoice_date';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return InvoiceDateExtensionCollection::class;
    }

    public function getEntityClass(): string
    {
        return InvoiceDateExtensionEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new FkField('order_id', 'orderId', OrderDefinition::class),
            (new DateTimeField('invoice_date_time', 'invoiceDateTime')),

            new OneToOneAssociationField('order', 'order_id', 'id', OrderDefinition::class, false)
        ]);
    }
}