<?php declare(strict_types=1);

namespace LandimIT\Subscription\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\addFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubscriptionCartProcessor implements CartProcessorInterface
{
    private PercentagePriceCalculator $calculator;
    private TranslatorInterface $translator;
    private EntityRepositoryInterface $languageRepository;

    public function __construct(PercentagePriceCalculator $calculator, TranslatorInterface $translator, EntityRepositoryInterface $languageRepository)
    {
        $this->calculator = $calculator;
        $this->translator = $translator;
        $this->languageRepository = $languageRepository;
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $products = $this->findExampleProducts($toCalculate);

        // no example products found? early return
        if ($products->count() === 0) {
            return;
        }

        $discountLineItem = $this->createDiscount('SUBSCRIPTION_DISCOUNT', $context);

        // declare price definition to define how this price is calculated
        $definition = new PercentagePriceDefinition(
            -15,
            new LineItemRule(LineItemRule::OPERATOR_EQ, $products->getKeys())
        );

        $discountLineItem->setPriceDefinition($definition);

        // calculate price
        $discountLineItem->setPrice(
            $this->calculator->calculate($definition->getPercentage(), $products->getPrices(), $context)
        );

        // add discount to new cart
        $toCalculate->add($discountLineItem);
    }

    private function findExampleProducts(Cart $cart): LineItemCollection
    {
        return $cart->getLineItems()->filter(function (LineItem $item) {
            // Only consider products, not custom line items or promotional line items
            if ($item->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                return false;
            }


            $subscriptionItem = (
                $item->getExtensions() && 
                isset($item->getExtensions()['subscription'])
            );

            if (!$subscriptionItem) {
                return false;
            }

            return $item;
        });
    }

    private function createDiscount(string $name, SalesChannelContext $context): LineItem
    {

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('id', $context->getSalesChannel()->languageId)
        );

        $criteria->addAssociation('locale');
        //$criteria->addAggregation('price');

        $language = $this->languageRepository->search($criteria, $context->getContext())->first();

        $this->translator->setLocale($language->getLocale()->getCode());

        $discountLineItem = new LineItem($name, 'subscription_discount', null, 1);

        $discountLineItem->setLabel($this->translator->trans('product.subscriptionsDiscount'));
        $discountLineItem->setGood(false);
        $discountLineItem->setStackable(false);
        $discountLineItem->setRemovable(false);

        return $discountLineItem;
    }
}