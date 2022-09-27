<?php declare(strict_types=1);

namespace LandimIT\Subscription\Core\Content\SubscriptionLineItem;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Computed;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentFkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use LandimIT\Subscription\Core\Content\Subscription\SubscriptionDefinition;

class SubscriptionLineItemDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'landimit_subscription_line_item';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
    
    protected function defineFields(): FieldCollection
    {

        
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new FkField('subscription_id', 'subscriptionId', SubscriptionDefinition::class),
            new FkField('product_id', 'productId', ProductDefinition::class),
            (new StringField('label', 'label'))->addFlags(new ApiAware(), new Required()),
            (new IntField('quantity', 'quantity'))->addFlags(new Required()),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id', false),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new ApiAware(), new Required()),
            new ManyToOneAssociationField('subscription', 'subscription_id', SubscriptionDefinition::class, 'id', false),
            (new FloatField('unit_price', 'unitPrice'))->addFlags(new Required()),
            (new FloatField('total_price', 'totalPrice'))->addFlags(new Required()),
            new CreatedAtField(),
            new UpdatedAtField()
        ]);

    }

    public function getEntityClass(): string
    {
        return SubscriptionLineItemEntity::class;
    }
}