<?php declare(strict_types=1);

namespace LandimIT\Subscription\Core\Content\SubscriptionOrder;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use LandimIT\Subscription\Core\Content\Subscription\SubscriptionDefinition;

class SubscriptionOrderDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'landimit_subscription_order';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
    
    protected function defineFields(): FieldCollection
    {

        
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new FkField('subscription_id', 'subscriptionId', SubscriptionDefinition::class),
            new FkField('order_id', 'orderId', OrderDefinition::class),
            new ManyToOneAssociationField('order', 'order_id', OrderDefinition::class, 'id', false),
            new ManyToOneAssociationField('subscription', 'subscription_id', SubscriptionDefinition::class, 'id', false),
            (new ReferenceVersionField(OrderDefinition::class))->addFlags(new ApiAware(), new Required()),
            new CreatedAtField(),
            new UpdatedAtField()

        ]);

    }

    public function getEntityClass(): string
    {
        return SubscriptionOrderEntity::class;
    }
}