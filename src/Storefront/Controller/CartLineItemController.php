<?php declare(strict_types=1);

namespace LandimIT\Subscription\Storefront\Controller;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Exception\InvalidQuantityException;
use Shopware\Core\Checkout\Cart\Exception\LineItemNotFoundException;
use Shopware\Core\Checkout\Cart\Exception\LineItemNotStackableException;
use Shopware\Core\Checkout\Cart\Exception\MixedLineItemTypeException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Promotion\Cart\PromotionCartAddedInformationError;
use Shopware\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Shopware\Core\Content\Product\Cart\ProductLineItemFactory;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Util\HtmlSanitizer;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher;
use Shopware\Storefront\Controller\CartLineItemController as ShopwareCartLineItemController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use LandimIT\Subscription\Components\Struct\Subscription;


/**
 * @RouteScope(scopes={"storefront"})
 */
class CartLineItemController extends ShopwareCartLineItemController
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var PromotionItemBuilder
     */
    private $promotionItemBuilder;

    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;
    private AbstractContextSwitchRoute $contextSwitchRoute;

    /**
     * @var ProductLineItemFactory
     */
    private $productLineItemFactory;

     public function __construct(
        CartService $cartService,
        SalesChannelRepositoryInterface $productRepository,
        PromotionItemBuilder $promotionItemBuilder,
        ProductLineItemFactory $productLineItemFactory,
        HtmlSanitizer $htmlSanitizer,
        SalesChannelContextSwitcher $contextSwitcher,
        EntityRepositoryInterface $paymentMethodRepository,
        AbstractContextSwitchRoute $contextSwitchRoute
    ) {
        parent::__construct($cartService, $productRepository, $promotionItemBuilder, $productLineItemFactory, $htmlSanitizer);
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->promotionItemBuilder = $promotionItemBuilder;
        $this->productLineItemFactory = $productLineItemFactory;
        $this->htmlSanitizer = $htmlSanitizer;
        $this->contextSwitcher = $contextSwitcher;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->contextSwitchRoute = $contextSwitchRoute;

    }



    /**
     * @Since("6.0.0.0")
     * @Route("/checkout/line-item/add", name="frontend.checkout.line-item.add", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     *
     * requires the provided items in the following form
     * 'lineItems' => [
     *     'anyKey' => [
     *         'id' => 'someKey'
     *         'quantity' => 2,
     *         'type' => 'someType'
     *     ],
     *     'randomKey' => [
     *         'id' => 'otherKey'
     *         'quantity' => 2,
     *         'type' => 'otherType'
     *     ]
     * ]
     *
     * @throws InvalidQuantityException
     * @throws LineItemNotStackableException
     * @throws MissingRequestParameterException
     * @throws MixedLineItemTypeException
     */
    public function addLineItems(Cart $cart, RequestDataBag $requestDataBag, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        /** @var RequestDataBag|null $lineItems */
        $lineItems = $requestDataBag->get('lineItems');
        if (!$lineItems) {
            throw new MissingRequestParameterException('lineItems');
        }
        $count = 0;

        try {
            $items = [];
            /** @var RequestDataBag $lineItemData */
            foreach ($lineItems as $lineItemData) {
                $lineItem = new LineItem(
                    $lineItemData->getAlnum('id'),
                    $lineItemData->getAlnum('type'),
                    $lineItemData->get('referencedId'),
                    $lineItemData->getInt('quantity', 1)
                );

                /*
                *
                * Landim IT - Add subscription to the cart line
                *
                */
                $lineItem = $this->subscription($lineItem, $salesChannelContext, $lineItemData);


                $lineItem->setStackable($lineItemData->getBoolean('stackable', true));
                $lineItem->setRemovable($lineItemData->getBoolean('removable', true));

                $count += $lineItem->getQuantity();

                $items[] = $lineItem;
            }

            $cart = $this->cartService->add($cart, $items, $salesChannelContext);

            if (!$this->traceErrors($cart)) {
                $this->addFlash(self::SUCCESS, $this->trans('checkout.addToCartSuccess', ['%count%' => $count]));
            }
        } catch (ProductNotFoundException $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.addToCartError'));
        }

        return $this->createActionResponse($request);
    }

    private function subscription(LineItem $lineItem, SalesChannelContext $salesChannelContext, $lineItemData): LineItem
    {


        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', 'stripe.shopware_payment.payment_handler.card'));

        $paymentMethod = $this->paymentMethodRepository->search($criteria, $salesChannelContext->getContext())->first();


        if((int)$lineItemData->get('subscription_option')) {

            $lineItem->addExtension('subscription', new Subscription(
                    (int)$lineItemData->get('subscription_interval')
            ));
            

            $this->contextSwitchRoute->switchContext(
                new RequestDataBag([
                    SalesChannelContextService::SHIPPING_METHOD_ID => $salesChannelContext->getShippingMethod()->getId(),
                    SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethod->getId(),
                ]),
                $salesChannelContext
            );
 

        }


        return $lineItem;

    }

    private function traceErrors(Cart $cart): bool
    {
        if ($cart->getErrors()->count() <= 0) {
            return false;
        }

        $this->addCartErrors($cart, function (Error $error) {
            return $error->isPersistent();
        });

        return true;
    }
}