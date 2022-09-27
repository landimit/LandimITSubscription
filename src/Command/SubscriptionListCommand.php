<?php declare(strict_types=1);

namespace LandimIT\Subscription\Command;

use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Checkout\Cart\CartPersisterInterface;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Processor;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\Product\Cart\ProductLineItemFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\CachedSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Stripe\PaymentIntent;
use Stripe\ShopwarePayment\Payment\PaymentIntentPaymentConfig\PaymentIntentConfig;
use Stripe\ShopwarePayment\StripeApi\StripeApi;
use Stripe\ShopwarePayment\StripeApi\StripeApiFactory;
use Stripe\Stripe;
use Stripe\StripeClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use LandimIT\Subscription\Core\Content\Subscription\SubscriptionEntity;
use LandimIT\Subscription\Components\Struct\Subscription;

class SubscriptionListCommand extends Command
{
    // Command name
    protected static $defaultName = 'landimit:subscription:list';
    private const SESSION_KEY_FACEBOOK_DEFAULT_EVENT_KEY = 'landimit.facebook.default.event_id';
    protected Processor $processor;

    public function __construct(
            EntityRepositoryInterface $subscriptionEntity,
            OrderService $orderService,
            CartService $cartService,
            EntityRepositoryInterface $salesChannelRepository,
            EntityRepositoryInterface $salesChannelTypeRepository,
            EntityRepositoryInterface $customerRepository,
            EntityRepositoryInterface $productRepository,
            EntityRepositoryInterface $orderRepository,
            EntityRepositoryInterface $stateMachineStateRepository,
            EntityRepositoryInterface $orderTransactionRepository,
            SalesChannelRepository $salesChannelProductRepository,
            CachedSalesChannelContextFactory $factory,
            CartCalculator $calculator,
            CartPersisterInterface $persister,
            Processor $processor,
            LineItemFactoryRegistry $productLineItemFactory,
            SystemConfigService $systemConfigService,
            StripeApiFactory $stripeApiFactory,
            EntityRepositoryInterface $subscriptionOrderEntity,
            StateMachineRegistry $stateMachineRegistry,
            OrderTransactionStateHandler $orderTransactionStateHandler,
            EntityRepositoryInterface $mailTemplateRepository,
            AbstractMailService $mailService     
    ) {
        parent::__construct();
        $this->subscriptionEntity = $subscriptionEntity;
        $this->subscriptionOrderEntity = $subscriptionOrderEntity;
        $this->cartService = $cartService;
        $this->orderService = $orderService;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->salesChannelTypeRepository = $salesChannelTypeRepository;
        $this->customerRepository = $customerRepository;
        $this->salesChannelProductRepository = $salesChannelProductRepository;
        $this->factory = $factory;
        $this->calculator = $calculator;
        $this->persister = $persister;
        $this->processor = $processor;
        $this->productLineItemFactory = $productLineItemFactory;
        $this->productRepository = $productRepository;
        $this->systemConfigService = $systemConfigService;
        $this->stripeApiFactory = $stripeApiFactory;
        $this->orderRepository = $orderRepository;
        $this->stateMachineStateRepository = $stateMachineStateRepository;
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->stripeSecretKey = $this->systemConfigService->get('StripeShopwarePayment.config.stripeSecretKey');
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailService = $mailService;


    }

    // Provides a description, printed out in bin/console
    protected function configure(): void
    {
        $this->setDescription('Does something very special.');
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {



        $criteria = new Criteria();
        $criteria->addAssociation('customer');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('salesChannel');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $criteria->addAssociation('lineItems.product.cover');
        $criteria->addAssociation('paymentMethod');
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        //$criteria->addAssociation('orders');

        $searchResult = $this->subscriptionEntity->search($criteria, Context::createDefaultContext());





        $table = new Table($output);
        $table->setHeaders(['order_id', 'created_at', 'interval', 'customer', 'stripe_tk', 'last_renew', 'next_renew', 'sales_channel']);


        $today = new \DateTime("NOW");
        foreach($searchResult as $subscription) {
            if($subscription->getNextRenew()->format('Ymd') <= $today->format('Ymd') && $subscription->getActive()) {

                $salesChannel = $subscription->getSalesChannel();
                $customer = $subscription->getCustomer();
                $subscriptionLineItems = $subscription->getLineItems();




                $token = Uuid::randomHex();
                $salesChannelContext = $this->factory->create(
                        $token, 
                        $salesChannel->getId(),
                        [SalesChannelContextService::CUSTOMER_ID => $customer->getId()]
                );





                $token = Uuid::randomHex();
                $cart = $this->cartService->createNew($token, __METHOD__);

                // Create product line item

                foreach($subscriptionLineItems as $subscriptionLineItem) {
                    $quantity = $subscriptionLineItem->getQuantity();
                    $product = $subscriptionLineItem->getProduct();

                    $lineItem = $this->productLineItemFactory->create([
                        'type' => 'product',
                        'referencedId' => $product->getId(),
                        'quantity' => $quantity
                    ], $salesChannelContext);

                    $lineItem->addExtension('subscription', new Subscription(
                        $subscription->getInterval()
                    ));

                    $cart = $this->cartService->add($cart, [$lineItem], $salesChannelContext);

                }




                $this->calculator->calculate($cart, $salesChannelContext);




                $this->persister->save($cart, $salesChannelContext);






                $paymentMethod = $subscription->getPaymentMethod();


                $dataBag = new RequestDataBag([
                        SalesChannelContextService::SHIPPING_METHOD_ID => $salesChannelContext->getShippingMethod()->getId(),
                        SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethod->getId(),

                ]);

                $orderId = $this->cartService->order($cart, $salesChannelContext, $dataBag);

                $order = $this->getOrder($salesChannelContext, $orderId);

                $customFields = $order->getCustomFields();

                $customFields['custom_marketing_channel'] = "Subscription";


                if(sizeof($customFields) > 0) {
                    $this->orderRepository->update([
                        [
                            'id' => $order->getId(),
                            'customFields' => $customFields
                        ]
                    ], $salesChannelContext->getContext());            
                }


                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('orderId', $orderId));


                $orderTrasactionEntity = $this
                        ->orderTransactionRepository
                        ->search(
                            $criteria, 
                            $salesChannelContext->getContext()
                        )->first();

                $this->orderTransactionRepository->update([
                    [
                            'id' => $orderTrasactionEntity->getId(),
                            'paymentMethodId' => $paymentMethod->getId()
                    ]
                ], $salesChannelContext->getContext());






                $id = Uuid::randomHex();

                $this->subscriptionOrderEntity->create([
                        [
                            'id' => $id,
                            'orderId' => $orderId,
                            'subscriptionId' => $subscription->getId()
                        ]
                ], $salesChannelContext->getContext());

                $nextRenew = new \DateTime("NOW");

                $days = ($subscription->getInterval()/60/60/24); //in days
                $nextRenew = new \DateTime("NOW");
                $nextRenew->modify('+'.$days.' days');


                $this->subscriptionEntity->update([
                    [
                        'id' => $subscription->getId(),
                        'lastRenew' => new \DateTime("NOW"),
                        'nextRenew' => $nextRenew
                    ]
                ], $salesChannelContext->getContext());








                if(
                    $paymentMethod->getShortName() == "invoice_payment"
                ) {

                    $this->sendEmailRenewed($subscription->getId(), $salesChannelContext);

                }   

                if(
                    $paymentMethod->getShortName() == "pre_payment"
                ) {

                    $this->sendEmailRenewed($subscription->getId(), $salesChannelContext);

                }   

                if(
                    $paymentMethod->getShortName() == 'stripe.shopware_payment.payment_handler.card'
                ) {


                    $stripeCustomerId = $customer->getCustomFields()['stripeCustomerId'];

                    $stripeApi = $this->stripeApiFactory->createStripeApiForSalesChannel(
                        $salesChannelContext->getContext(),
                        $salesChannel->getId()
                    );

                    $availableCards = $stripeApi->getSavedCardsOfStripeCustomer(
                        $customer->getCustomFields()['stripeCustomerId']
                    );

                    //TODO
                    $stripe = new StripeClient($this->stripeSecretKey);
                    $stripePm = null;


                    // Stripe PI Payment
                    if(!$stripePm && isset($subscription->getCustomFields()['stripe_payment_context']['payment']['payment_intent_id'])) {
                        $pms = $stripe->paymentIntents->retrieve(
                          $subscription->getCustomFields()['stripe_payment_context']['payment']['payment_intent_id'],
                          []
                        );
                        $stripePm = $pms->payment_method;
                    }

                    // Stripe setup intent Payment

                    if(!$stripePm && isset($subscription->getCustomFields()['stripe_payment_context']['payment']['setup_intent'])) {

                        $pms = $stripe->setupIntents->retrieve(
                          $subscription->getCustomFields()['stripe_payment_context']['payment']['setup_intent'],
                          []
                        );

                        $stripePm = $pms->payment_method;

                    }


                    $amount = (int)($cart->getPrice()->getTotalPrice()*100);
                    $currency = $salesChannelContext->getCurrency()->getIsoCode();

                    $paymentIntentConfig = new PaymentIntentConfig();
                    $paymentIntentConfig->setStripeCustomer($stripeApi->getCustomer($stripeCustomerId));
                    $paymentIntentConfig->setAmountToPayInSmallestCurrencyUnit($amount);
                    $paymentIntentConfig->setCurrencyIsoCode($currency);
                    $paymentIntentConfig->setStripePaymentMethodId($stripePm);






                    $paymentIntent = $stripeApi->createPaymentIntent($paymentIntentConfig);

                    if($paymentIntent->status == "succeeded") {

                        $this->sendEmailRenewed($subscription->getId(), $salesChannelContext);

                        $this->orderTransactionStateHandler->paid(
                            $orderTrasactionEntity->getId(), 
                            $salesChannelContext->getContext()
                        );
                        
                    } else {

                        $this->sendEmailPaymentFailed($subscription->getId(), $salesChannelContext);

                    }



                }



               

                $table->addRow(
                    [
                        $orderId,
                        $subscription->getCreatedAt()->format('Y-m-d H:i:s'),
                        $subscription->getInterval(),
                        $subscription->getCustomer()->getEmail(),
                        '',
                        $subscription->getLastRenew()->format('Y-m-d H:i:s'),
                        $subscription->getNextRenew()->format('Y-m-d H:i:s'),
                        $subscription->getSalesChannel()->getId()
                    ]
                );
            }

        }

        $table->render();
        //$output->writeln(get_class($this->subscriptionEntity));

        // Exit code 0 for success
        return 0;
    }


    private function sendEmailRenewed($subscriptionId, $salesChannelContext) {

        $subscription = $this->getSubscription($salesChannelContext, $subscriptionId);

        $customer = $subscription->getCustomer();

        $data = new ParameterBag();
        $data->set(
            'recipients',
            [
                $customer->getEmail() => $customer->getFirstName() . ' ' . $customer->getLastName()
            ]
        );

        foreach ($salesChannelContext->getSalesChannel()->getDomains() as $domain) {
            $url = $domain->getUrl();
        }

        $mailTemplate = $this->getMailTemplate($salesChannelContext, 'subscription_renew_mail_template_type');

        $data->set('senderName', $mailTemplate->getSenderName());
        $data->set('contentHtml', $mailTemplate->getContentHtml());
        $data->set('contentPlain', $mailTemplate->getContentPlain());
        $data->set('subject', $mailTemplate->getSubject());


        $data->set('salesChannelId', $salesChannelContext->getSalesChannel()->getId());

        $this->mailService->send(
            $data->all(),
            $salesChannelContext->getContext(),
            [
                'subscription' => $subscription,
                'url' => $url
            ]
        );


    }


    private function sendEmailPaymentFailed($subscriptionId, $salesChannelContext) {


        $subscription = $this->getSubscription($salesChannelContext, $subscriptionId);
        $customer = $subscription->getCustomer();

        $data = new ParameterBag();
        $data->set(
            'recipients',
            [
                $customer->getEmail() => $customer->getFirstName() . ' ' . $customer->getLastName()
            ]
        );

        foreach ($salesChannelContext->getSalesChannel()->getDomains() as $domain) {
            $url = $domain->getUrl();
        }

        $mailTemplate = $this->getMailTemplate($salesChannelContext, 'subscription_payment_failed_mail_template_type');

        $data->set('senderName', $mailTemplate->getSenderName());
        $data->set('contentHtml', $mailTemplate->getContentHtml());
        $data->set('contentPlain', $mailTemplate->getContentPlain());
        $data->set('subject', $mailTemplate->getSubject());


        $data->set('salesChannelId', $salesChannelContext->getSalesChannel()->getId());

        $this->mailService->send(
            $data->all(),
            $salesChannelContext->getContext(),
            [
                'subscription' => $subscription,
                'url' => $url
            ]
        );


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

   
    private function getOrder(SalesChannelContext $salesChannelContext, string $orderId): ?OrderEntity
    {
        $criteria = new Criteria([$orderId]);

        $order = $this->orderRepository->search($criteria, $salesChannelContext->getContext())->first();
        return $order;
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
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        $subscription = $this->subscriptionEntity->search($criteria, $salesChannelContext->getContext())->first();
        return $subscription;
    }



}