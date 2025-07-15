<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;

class CategoryImpressionsDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cbax_analytics_category_impressions';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

	public function getEntityClass(): string
    {
        return CategoryImpressionsEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CategoryImpressionsCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('category_id', 'categoryId', CategoryDefinition::class))->addFlags(new Required()),
            (new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class))->addFlags(new Required()),
            new FkField('customer_group_id', 'customerGroupId', CustomerGroupDefinition::class),
            (new DateField('date', 'date'))->addFlags(new Required()),
            new IntField('impressions', 'impressions'),
            new StringField('device_type', 'deviceType')
        ]);
    }
}
