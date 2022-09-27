<?php declare(strict_types=1);

namespace LandimIT\Subscription\Core\Content\SubscriptionLineItem;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(ExampleEntity $entity)
 * @method void               set(string $key, ExampleEntity $entity)
 * @method ExampleEntity[]    getIterator()
 * @method ExampleEntity[]    getElements()
 * @method ExampleEntity|null get(string $key)
 * @method ExampleEntity|null first()
 * @method ExampleEntity|null last()
 */
class SubscriptionLineItemCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SubscriptionLineItemEntity::class;
    }
}