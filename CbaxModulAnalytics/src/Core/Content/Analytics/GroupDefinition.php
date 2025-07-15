<?php declare(strict_types=1);

namespace Cbax\ModulAnalytics\Core\Content\Analytics;

use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;

class GroupDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cbax_analytics_groups_config';

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
            (new StringField('label', 'label'))->addFlags(new Required()),
            new IntField('position', 'position'),
            (new IntField('active', 'active'))->addFlags(new Required()),
            (new StringField('plugin', 'plugin'))->addFlags(new Required()),
            new StringField('parameter', 'parameter'),
            
            new OneToManyAssociationField('statistics', ConfigDefinition::class, 'group_id')
        ]);
    }
}

