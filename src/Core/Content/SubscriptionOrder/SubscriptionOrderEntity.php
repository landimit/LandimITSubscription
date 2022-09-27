<?php declare(strict_types=1);

namespace LandimIT\Subscription\Core\Content\SubscriptionOrder;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use LandimIT\Core\Content\Subscription\SubscriptionEntity;

class SubscriptionOrderEntity extends Entity
{
    use EntityIdTrait;


    /**
     * @var CustomerEntity
     */
    protected $subscriptionId;
    protected $orderId;
    protected $subscription;
    protected $order;
    protected $createdAt;
    protected $updatedAt;





    public function getSubscriptionId(): ?string
    {
        return $this->subscriptionId;
    }

    public function setSubscriptionId(?string $subscriptionId): void
    {
        $this->subscriptionId = $subscriptionId;
    }

 
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    

    public function getSubscription(): SubscriptionEntity
    {
        return $this->subscription;
    }

    public function setSubscription(SubscriptionEntity $subscription): void
    {
        $this->subscription = $subscription;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function setOrder(OrderEntity $order): void
    {
        $this->order = $order;
    }
    

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
  
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

}