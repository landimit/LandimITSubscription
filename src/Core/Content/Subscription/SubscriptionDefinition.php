<?php declare(strict_types=1);

namespace LandimIT\Subscription\Core\Content\Subscription;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Computed;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use LandimIT\Subscription\Core\Content\SubscriptionLineItem\SubscriptionLineItemDefinition;
use LandimIT\Subscription\Core\Content\SubscriptionOrder\SubscriptionOrderDefinition;

class SubscriptionDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'landimit_subscription';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }
    
    protected function defineFields(): FieldCollection
    {


        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new IntField('auto_increment', 'autoIncrement'),
            new FkField('customer_id', 'customerId', CustomerDefinition::class),
            new FkField('sales_channel_id', 'salesChannelId', SalesChannelDefinition::class),
            new FkField('currency_id', 'currencyId', CurrencyDefinition::class),
            (new IntField('interval', 'interval'))->addFlags(new Required()),
            (new DateTimeField('last_renew', 'lastRenew'))->addFlags(new ApiAware(), new Inherited()),
            (new DateTimeField('next_renew', 'nextRenew'))->addFlags(new ApiAware(), new Inherited()),
            new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false),
            new ManyToOneAssociationField('salesChannel', 'sales_channel_id', SalesChannelDefinition::class, 'id'),
            new ManyToOneAssociationField('currency', 'currency_id', CurrencyDefinition::class, 'id'),
            (new OneToManyAssociationField('lineItems', SubscriptionLineItemDefinition::class, 'subscription_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new OneToManyAssociationField('orders', SubscriptionOrderDefinition::class, 'subscription_id'))->addFlags(new ApiAware(), new CascadeDelete()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            (new BoolField('archived', 'archived'))->addFlags(new ApiAware()),
            (new CustomFields())->addFlags(new ApiAware()),
            new CreatedAtField(),
            new UpdatedAtField(),
            (new FloatField('total_price', 'totalPrice'))->addFlags(new Required()),
            (new FloatField('discount', 'discount'))->addFlags(new Required()),
            (new FkField('payment_method_id', 'paymentMethodId', PaymentMethodDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new ManyToOneAssociationField('paymentMethod', 'payment_method_id', PaymentMethodDefinition::class, 'id', false))->addFlags(new ApiAware()),
        ]);

    }

    public function getEntityClass(): string
    {
        return SubscriptionEntity::class;
    }
}