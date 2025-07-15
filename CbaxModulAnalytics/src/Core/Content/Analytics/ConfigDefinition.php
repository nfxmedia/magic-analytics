<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;

class ConfigDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cbax_analytics_config';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

	public function getEntityClass(): string
    {
        return ConfigEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ConfigCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('name', 'name'))->addFlags(new Required()),
            new StringField('group_name', 'groupName'),
            (new StringField('label', 'label'))->addFlags(new Required()),
            new StringField('route_name', 'routeName'),
            (new StringField('path_info', 'pathInfo'))->addFlags(new Required()),
            new IntField('position', 'position'),
            (new IntField('active', 'active'))->addFlags(new Required()),
            (new StringField('plugin', 'plugin'))->addFlags(new Required()),
            new JsonField('parameter', 'parameter'),

            (new FkField('group_id', 'groupId', GroupDefinition::class)),
            new ManyToOneAssociationField('group', 'group_id', GroupDefinition::class, 'id')
        ]);
    }
}
