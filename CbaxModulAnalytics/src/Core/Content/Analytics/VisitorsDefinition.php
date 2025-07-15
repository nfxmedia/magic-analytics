<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

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

class VisitorsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cbax_analytics_visitors';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

	public function getEntityClass(): string
    {
        return VisitorsEntity::class;
    }

    public function getCollectionClass(): string
    {
        return VisitorsCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            (new DateField('date', 'date'))->addFlags(new Required()),
            new IntField('page_impressions', 'pageImpressions'),
            new IntField('unique_visits', 'uniqueVisits'),
            new StringField('device_type', 'deviceType'),
        ]);
    }
}
