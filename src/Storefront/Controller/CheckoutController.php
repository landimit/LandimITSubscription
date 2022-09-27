<?php declare(strict_types=1);

namespace LandimIT\Subscription\Storefront\Controller;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\Exception\CartTokenNotFoundException;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractLogoutRoute;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Exception\EmptyCartException;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\Exception\PaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\UnknownPaymentMethodException;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Checkout\Cart\Error\PaymentMethodChangedError;
use Shopware\Storefront\Checkout\Cart\Error\ShippingMethodChangedError;
use Shopware\Storefront\Controller\CheckoutController as ShopwareCheckoutController;
use Shopware\Storefront\Framework\AffiliateTracking\AffiliateTrackingListener;
use Shopware\Storefront\Framework\Routing\Annotation\NoStore;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedHook;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedHook;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoader;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedHook;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoader;
use Shopware\Storefront\Page\Checkout\Offcanvas\CheckoutInfoWidgetLoadedHook;
use Shopware\Storefront\Page\Checkout\Offcanvas\CheckoutOffcanvasWidgetLoadedHook;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoader;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use LandimIT\Subscription\Core\Content\Subscription\SubscriptionEntity;
/**
 * @RouteScope(scopes={"storefront"})
 */
class CheckoutController extends ShopwareCheckoutController
{
    private const REDIRECTED_FROM_SAME_ROUTE = 'redirected';

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var CheckoutCartPageLoader
     */
    private $cartPageLoader;

    /**
     * @var CheckoutConfirmPageLoader
     */
    private $confirmPageLoader;

    /**
     * @var CheckoutFinishPageLoader
     */
    private $finishPageLoader;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var OffcanvasCartPageLoader
     */
    private $offcanvasCartPageLoader;

    /**
     * @var SystemConfigService
     */
    private $config;

    /**
     * @var AbstractLogoutRoute
     */
    private $logoutRoute;

    public function __construct(
        CartService $cartService,
        CheckoutCartPageLoader $cartPageLoader,
        CheckoutConfirmPageLoader $confirmPageLoader,
        CheckoutFinishPageLoader $finishPageLoader,
        OrderService $orderService,
        PaymentService $paymentService,
        OffcanvasCartPageLoader $offcanvasCartPageLoader,
        SystemConfigService $config,
        AbstractLogoutRoute $logoutRoute,
        EntityRepositoryInterface $subscriptionEntity,
        EntityRepositoryInterface $subscriptionLineItemEntity,
        EntityRepositoryInterface $subscriptionOrderEntity,
        SessionInterface $session,
        EntityRepositoryInterface $orderTransactionRepository,
        EntityRepositoryInterface $mailTemplateRepository,
        AbstractMailService $mailService     
    ) {

        parent::__construct($cartService, $cartPageLoader, $confirmPageLoader, $finishPageLoader, $orderService, $paymentService, $offcanvasCartPageLoader, $config, $logoutRoute);

        $this->cartService = $cartService;
        $this->cartPageLoader = $cartPageLoader;
        $this->confirmPageLoader = $confirmPageLoader;
        $this->finishPageLoader = $finishPageLoader;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->offcanvasCartPageLoader = $offcanvasCartPageLoader;
        $this->config = $config;
        $this->logoutRoute = $logoutRoute;
        $this->subscriptionEntity = $subscriptionEntity;
        $this->subscriptionLineItemEntity = $subscriptionLineItemEntity;
        $this->subscriptionOrderEntity = $subscriptionOrderEntity;
        $this->session = $session;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailService = $mailService;

    }


    /**
     * @Since("6.0.0.0")
     * @Route("/checkout/order", name="frontend.checkout.finish.order", options={"seo"="false"}, methods={"POST"})
     */
    public function order(RequestDataBag $data, SalesChannelContext $context, Request $request): Response
    {
        if (!$context->getCustomer()) {
            return $this->redirectToRoute('frontend.checkout.register.page');
        }

        $savedCart = $this->cartService->getCart($context->getToken(), $context);

        try {
            $this->addAffiliateTracking($data, $request->getSession());

            $orderId = $this->orderService->createOrder($data, $context);
        } catch (ConstraintViolationException $formViolations) {
            return $this->forwardToRoute('frontend.checkout.confirm.page', ['formViolations' => $formViolations]);
        } catch (InvalidCartException | Error | EmptyCartException $error) {
            $this->addCartErrors(
                $this->cartService->getCart($context->getToken(), $context)
            );

            return $this->forwardToRoute('frontend.checkout.confirm.page');
        }

        try {
            $finishUrl = $this->generateUrl('frontend.checkout.finish.page', ['orderId' => $orderId]);
            $errorUrl = $this->generateUrl('frontend.account.edit-order.page', ['orderId' => $orderId]);

            $response = $this->paymentService->handlePaymentByOrder($orderId, $data, $context, $finishUrl, $errorUrl);

            $paymentMethod = $this->getPaymentMethod($context, $orderId);

            if( 
                $response
            ) {
                $this->saveSubscription($savedCart, $context, $request, $orderId);
            }

            return $response ?? new RedirectResponse($finishUrl);
        } catch (PaymentProcessException | InvalidOrderException | UnknownPaymentMethodException $e) {
            return $this->forwardToRoute('frontend.checkout.finish.page', ['orderId' => $orderId, 'changedPayment' => false, 'paymentFailed' => true]);
        }
    }

    private function saveSubscription(Cart $cart, SalesChannelContext $context, Request $request, $orderId): int
    {
        
        $paymentMethod = $this->getPaymentMethod($context, $orderId);
      


        $customer = $context->getCustomer();
        $subscriptions = [];

        $orderTrasactionEntity = $this->getOrderTransaction($context, $orderId);

        $customFields = $orderTrasactionEntity->getCustomFields();
        $paymentMethod = $orderTrasactionEntity->getPaymentMethod();

        $linesTotal = [];
        $linesUnit = [];
        $linesLabel = [];
        $discount = 0;
        $total = 0;

        foreach($cart->getLineItems() as $key => $lineItem) {

            if($lineItem->getExtensions() && isset($lineItem->getExtensions()['subscription'])) {

                $productId = $lineItem->getId();
                $interval = $lineItem->getExtensions()['subscription']->getInterval();
                $quantity = $lineItem->getQuantity();
                $subscriptions[$interval][$productId] = array(
                    'quantity' => $quantity
                );

                $linesTotal[$productId] = $lineItem->getPrice()->getTotalPrice();
                $linesUnit[$productId] = $lineItem->getPrice()->getUnitPrice();
                $linesLabel[$productId] = $lineItem->getLabel();
                $total += $lineItem->getPrice()->getTotalPrice();

            }

            if($key == "SUBSCRIPTION_DISCOUNT")
                $discount = $lineItem->getPrice()->getTotalPrice();

        }


        $salesChannel = $context->getSalesChannel();

        foreach ($subscriptions as $interval => $subscription) {

                $subscriptionId = Uuid::randomHex();
                $days = ($interval/60/60/24); //in days
                $nextRenew = new \DateTime("NOW");
                $nextRenew->modify('+'.$days.' days');
                
                $this->subscriptionEntity->create([
                        [
                            'id' => $subscriptionId,
                            'customerId' => $customer->getId(),
                            'salesChannelId' => $salesChannel->getId(),
                            'interval' => $interval,
                            'lastRenew' => new \DateTime("NOW"),
                            'nextRenew' => $nextRenew,
                            'customFields' => $customFields,
                            'active' => true,
                            'totalPrice' => $total,
                            'currencyId' => $context->getCurrency()->getId(),
                            'discount' => $discount,
                            'paymentMethodId' => $paymentMethod->getId()
                        ]
                ], $context->getContext());
                
                $id = Uuid::randomHex();

                $this->subscriptionOrderEntity->create([
                        [
                            'id' => $id,
                            'orderId' => $orderId,
                            'subscriptionId' => $subscriptionId
                        ]
                ], $context->getContext());


                foreach($subscription as $productId => $product) {
                    $id = Uuid::randomHex();

                    $this->subscriptionLineItemEntity->create([
                            [
                                'id' => $id,
                                'subscriptionId' => $subscriptionId,
                                'productId' => $productId,
                                'quantity' => $product['quantity'],
                                'totalPrice' => $linesTotal[$productId],
                                'unitPrice' => $linesUnit[$productId],
                                'label' => $linesLabel[$productId]
                            ]
                    ], $context->getContext());
                }



                $data = new ParameterBag();
                $data->set(
                    'recipients',
                    [
                        $customer->getEmail() => $customer->getFirstName() . ' ' . $customer->getLastName()
                    ]
                );


                $mailTemplate = $this->getMailTemplate($context, 'subscription_mail_template_type');

                $data->set('senderName', $mailTemplate->getSenderName());
                $data->set('contentHtml', $mailTemplate->getContentHtml());
                $data->set('contentPlain', $mailTemplate->getContentPlain());
                $data->set('subject', $mailTemplate->getSubject());


                $data->set('salesChannelId', $context->getSalesChannel()->getId());

                $this->mailService->send(
                    $data->all(),
                    $context->getContext(),
                    [
                        'subscription' => $this->getSubscription($context, $subscriptionId),
                        'url' => $request->attributes->get('sw-storefront-url')
                    ]
                );

              


        }



     
        return 1;
    }


    private function getOrderTransaction(SalesChannelContext $salesChannelContext, string $orderId): ?OrderTransactionEntity
    {

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addAssociation('paymentMethod');

        return $this
        ->orderTransactionRepository
        ->search(
            $criteria, 
            $salesChannelContext->getContext()
        )->first();

    }
    
    private function getPaymentMethod(SalesChannelContext $salesChannelContext, string $orderId): ?PaymentMethodEntity
    {


        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        $criteria->addAssociation('paymentMethod');

        $orderTrasactionEntity = $this
        ->orderTransactionRepository
        ->search(
            $criteria, 
            $salesChannelContext->getContext()
        )->first();
        return $orderTrasactionEntity->getPaymentMethod();
    }

    private function getMailTemplate(SalesChannelContext $salesChannelContext, string $technicalName): ?MailTemplateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->setLimit(1);

        /** @var MailTemplateEntity|null $mailTemplate */
        $mailTemplate = $this->mailTemplateRepository->search($criteria, $salesChannelContext->getContext())->first();
        return $mailTemplate;
    }

    private function getSubscription(SalesChannelContext $salesChannelContext, string $subscriptionId): ?SubscriptionEntity
    {
        $criteria = new Criteria([$subscriptionId]);
        $criteria->addAssociation('customer');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('salesChannel');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $criteria->addAssociation('lineItems.product.cover');
        $criteria->addAssociation('paymentMethod');
        $criteria->setLimit(1);

        /** @var MailTemplateEntity|null $mailTemplate */
        $subscription = $this->subscriptionEntity->search($criteria, $salesChannelContext->getContext())->first();
        return $subscription;
    }

    private function addAffiliateTracking(RequestDataBag $dataBag, SessionInterface $session): void
    {
        $affiliateCode = $session->get(AffiliateTrackingListener::AFFILIATE_CODE_KEY);
        $campaignCode = $session->get(AffiliateTrackingListener::CAMPAIGN_CODE_KEY);
        if ($affiliateCode) {
            $dataBag->set(AffiliateTrackingListener::AFFILIATE_CODE_KEY, $affiliateCode);
        }

        if ($campaignCode) {
            $dataBag->set(AffiliateTrackingListener::CAMPAIGN_CODE_KEY, $campaignCode);
        }
    }

    private function routeNeedsReload(ErrorCollection $cartErrors): bool
    {
        foreach ($cartErrors as $error) {
            if ($error instanceof ShippingMethodChangedError || $error instanceof PaymentMethodChangedError) {
                return true;
            }
        }

        return false;
    }

    private function serializeCart(Cart $cart): string
    {
        $errors = $cart->getErrors();
        $data = $cart->getData();

        $cart->setErrors(new ErrorCollection());
        $cart->setData(null);

        $serializedCart = serialize($cart);

        $cart->setErrors($errors);
        $cart->setData($data);

        return $serializedCart;
    }
}
