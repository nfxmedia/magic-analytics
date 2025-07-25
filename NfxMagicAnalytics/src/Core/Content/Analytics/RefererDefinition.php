<?php declare(strict_types=1);

namespace Nfx\MagicAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class RefererDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'nfx_analytics_referer';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

	public function getEntityClass(): string
    {
        return RefererEntity::class;
    }

    public function getCollectionClass(): string
    {
        return RefererCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new DateField('date', 'date'))->addFlags(new Required()),
            new StringField('referer', 'referer'),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            new IntField('counted', 'counted'),
            new StringField('device_type', 'deviceType')
        ]);
    }
}
