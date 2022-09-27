<?php declare(strict_types=1);

namespace LandimIT\Subscription\Core\Content\Subscription;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use LandimIT\Core\Content\SubscriptionLineItem\SubscriptionLineItemCollection;
use LandimIT\Core\Content\SubscriptionOrder\SubscriptionOrderCollection;

class SubscriptionEntity extends Entity
{
    use EntityIdTrait;
    use EntityCustomFieldsTrait;


    /**
     * @var CustomerEntity
     */
    protected $customerId;
    protected $salesChannelId;

    protected $customer;
    protected $salesChannel;

    protected $interval;
    protected $stripeTk;
    protected $lastRenew;
    protected $nextRenew;
    protected $intervalName;
    protected $createdAt;
    protected $updatedAt;
    /**
     * @var OrderLineItemCollection|null
     */
    protected $lineItems;
    protected $active;
    protected $cancelled;
    protected $cart;
    protected $autoIncrement;


    protected $currencyId;
    protected $currency;
    protected $unitPrice;
    protected $totalPrice;
    protected $discount;

    /**
     * @var string
     */
    protected $paymentMethodId;

    /**
     * @var PaymentMethodEntity|null
     */
    protected $paymentMethod;


    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }


    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): void
    {
        $this->salesChannelId = $salesChannelId;
    }


    public function getCustomer(): CustomerEntity
    {
        return $this->customer;
    }

    public function setCustomer(CustomerEntity $customer): void
    {
        $this->customer = $customer;
    }


    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }


    public function getInterval(): ?int
    {
        return $this->interval;
    }

    public function setInterval(?int $interval): void
    {
        $this->interval = $interval;
    }

    public function getLastRenew(): ?\DateTimeInterface
    {
        return $this->lastRenew;
    }

    public function setLastRenew(?\DateTimeInterface $lastRenew): void
    {
        $this->lastRenew = $lastRenew;
    }

    public function getNextRenew(): ?\DateTimeInterface
    {
        return $this->nextRenew;
    }

    public function setNextRenew(?\DateTimeInterface $nextRenew): void
    {
        $this->nextRenew = $nextRenew;
    }

    public function getIntervalName(): string
    {
        $week = 60*60*24*7;

        switch($this->interval) {
            case (2*$week):
                $this->intervalName = '2weeks';
                break;
            case (4*$week):
                $this->intervalName = '4weeks';
                break;
            case (6*$week):
                $this->intervalName = '6weeks';
                break;
            case (8*$week):
                $this->intervalName = '8weeks';
                break;
            default:
                $this->intervalName = 'Onetimepurchase';
                break;
        }


        return $this->intervalName;
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

    public function getLineItems(): ?EntityCollection
    {
        return $this->lineItems;
    }


    public function getOrders(): SubscriptionOrderCollection
    {
        return $this->orders;
    }


    public function getActive(): ?bool
    {
        return $this->active;
    }


    public function setActive(?bool $active): void
    {
        $this->active = $active;
    }    

    public function getArchived(): ?bool
    {
        return $this->archived;
    }


    public function setArchived(?bool $archived): void
    {
        $this->archived = $archived;
    }


    public function getAutoIncrement(): int
    {
        return $this->autoIncrement;
    }

    public function setAutoIncrement(int $autoIncrement): void
    {
        $this->autoIncrement = $autoIncrement;
    }



    public function getCurrencyId(): string
    {
        return $this->currencyId;
    }
    public function setCurrencyId(string $currencyId): void
    {
        $this->currencyId = $currencyId;
    }


    public function getCurrency(): CurrencyEntity
    {
        return $this->currency;
    }
    public function setCurrency(CurrencyEntity $currency): void
    {
        $this->currency = $currency;
    }


    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }
    public function setTotalPrice(float $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }
    
    public function getDiscount(): float
    {
        return $this->discount;
    }
    public function setDiscount(float $discount): void
    {
        $this->discount = $discount;
    }


    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }


    public function getPaymentMethod(): ?PaymentMethodEntity
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethodEntity $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }



}