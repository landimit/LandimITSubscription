<?php declare(strict_types=1);

namespace LandimIT\Subscription\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\LoginRequired;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use LandimIT\Subscription\Core\Content\Subscription\SubscriptionEntity;
use LandimIT\Subscription\Storefront\Page\Account\SubscriptionPageLoader;


class SubscriptionController extends StorefrontController
{
    /**
     * @var ExamplePageLoader
     */
    private $subscriptionPageLoader;

    private $db;



    public function __construct(
        SubscriptionPageLoader $subscriptionPageLoader
    ) {
        $this->subscriptionPageLoader = $subscriptionPageLoader;
        $this->stripeSecretKey = $this->subscriptionPageLoader->systemConfigService->get('StripeShopwarePayment.config.stripeSecretKey');
        $this->mailService = $this->subscriptionPageLoader->mailService;

        $this->subscriptionEntity = $this->subscriptionPageLoader->subscriptionRepository;
        $this->mailTemplateRepository = $this->subscriptionPageLoader->mailTemplateRepository;

    }

    /**
    * @LoginRequired()
    * @RouteScope(scopes={"storefront"})
    * @Route("/account/subscription", name="frontend.account.subscription.page", methods={"GET"})
    */
    public function indexPage(Request $request, SalesChannelContext $context): Response
    {

        $criteria = new Criteria();
        $criteria->addAssociation('customer');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('salesChannel');
        $criteria->addAssociation('lineItems');
        $criteria->addAssociation('lineItems.product');
        $criteria->addAssociation('lineItems.product.cover');
        $criteria->addAssociation('paymentMethod');

        $criteria->addFilter(
            new EqualsFilter('customer.id', $context->getCustomer()->getId())
        );


        $subscriptions = $this->subscriptionPageLoader->subscriptionRepository->search($criteria, $context->getContext());

    	$page = $this->subscriptionPageLoader->load($request, $context);

        foreach($subscriptions as $subscription) {
            

            $customFields = $subscription->getCustomFields();
            if(isset($customFields['stripe_payment_context']) && !isset($customFields['stripe_payment_context']['payment']['last4'])) {

                $externalData = $this->getCards($subscription);
                $customFields['stripe_payment_context']['payment']['last4'] = $externalData['card']['last4'];
                $customFields['stripe_payment_context']['payment']['brand'] = $externalData['card']['brand'];
                $customFields['stripe_payment_context']['payment']['exp_month'] = $externalData['card']['exp_month'];
                $customFields['stripe_payment_context']['payment']['exp_year'] = $externalData['card']['exp_year']; 

                $this->subscriptionPageLoader->subscriptionRepository->update([
                        [
                            'id' => $subscription->getId(),
                            'customFields' => $customFields
                        ]
                ], $context->getContext());


            }



        }


        return $this->renderStorefront('@LandimIT/storefront/page/account/subscription/index.html.twig', [
            'page' => $page,
            'subscriptions' => $subscriptions
        ]);
    }


    /**
     * @Since("6.2.0.0")
     * @LoginRequired()
     * @RouteScope(scopes={"storefront"})
     * @Route("/account/subscription/cancel", name="frontend.account.subscription.cancel", methods={"POST"})
     */
    public function cancelSubscription(Request $request, SalesChannelContext $context): Response
    {
        $subscriptionId = $request->get('subscriptionId');

        $customer = $context->getCustomer();

        $this->subscriptionPageLoader->subscriptionRepository->update([
                [
                    'id' => $subscriptionId,
                    'active' => false 
                ]
        ], $context->getContext());

        $data = new ParameterBag();
        $data->set(
            'recipients',
            [
                $customer->getEmail() => $customer->getFirstName() . ' ' . $customer->getLastName()
            ]
        );


        $mailTemplate = $this->getMailTemplate($context, 'subscription_cancel_mail_template_type');

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


        return $this->redirectToRoute('frontend.account.subscription.page');
    }

    /**
     * @LoginRequired()
     * @Since("6.2.0.0")
     * @RouteScope(scopes={"storefront"})
     * @Route("/account/subscription/activate", name="frontend.account.subscription.activate", methods={"POST"})
     */
    public function activateSubscription(Request $request, SalesChannelContext $context): Response
    {
        $subscriptionId = $request->get('subscriptionId');

        $customer = $context->getCustomer();

        $this->subscriptionPageLoader->subscriptionRepository->update([
                [
                    'id' => $subscriptionId,
                    'active' => true,
                    'nextRenew' => new \DateTime("NOW")
                ]
        ], $context->getContext());


        $data = new ParameterBag();
        $data->set(
            'recipients',
            [
                $customer->getEmail() => $customer->getFirstName() . ' ' . $customer->getLastName()
            ]
        );


        $mailTemplate = $this->getMailTemplate($context, 'subscription_reactivate_mail_template_type');

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

        return $this->redirectToRoute('frontend.account.subscription.page');
    }


    /**
     * @LoginRequired()
     * @RouteScope(scopes={"storefront"})
     * @Route(
     *     "/account/subscription/update_credit_card/{subscriptionId}",
     *     name="frontend.account.subscription.update_credit_card",
     *     methods={"GET"},
     *     requirements={"subscriptionId"="[a-fA-F0-9]{32}"},
     *     defaults={"XmlHttpRequest"=true}
     * )
     * @param string $languageId
     * @param Request $request
     * @param Context $context
     * @return Response
     */

    public function subscriptionUpdateCreditCardPage(string $subscriptionId, Request $request, SalesChannelContext $context): Response
    {

        $page = $this->subscriptionPageLoader->load($request, $context);


        $stripe = new StripeClient($this->stripeSecretKey);
        $paymentIntent = $stripe->setupIntents->create(
          [
            'customer' => $context->getCustomer()->getCustomFields()['stripeCustomerId'],
            'payment_method_types' => ['card'],
          ]
        );






        return $this->renderStorefront('@LandimIT/storefront/page/account/subscription/update_credit_card.html.twig', [
            'page' => $page,
            'subscriptionId' => $subscriptionId,
            'client_secret' => $paymentIntent->client_secret,
            'url' => $request->attributes->get('sw-storefront-url')
        ]);
    }


    /**
     * @LoginRequired()
     * @RouteScope(scopes={"storefront"})
     * @Route(
     *     "/account/subscription/update_credit_card/{subscriptionId}/confirm",
     *     name="frontend.account.subscription.update_credit_card_confirm",
     *     methods={"GET"},
     *     requirements={"subscriptionId"="[a-fA-F0-9]{32}"},
     *     defaults={"XmlHttpRequest"=true}
     * )
     * @param string $languageId
     * @param Request $request
     * @param Context $context
     * @return Response
     */

    public function subscriptionUpdateCreditCardConfirmPage(string $subscriptionId, Request $request, SalesChannelContext $context): Response
    {



        $setup_intent = $request->query->get('setup_intent');
        $setup_intent_client_secret = $request->query->get('setup_intent_client_secret');
        $json = [];



        $json['stripe_payment_context']['payment']['setup_intent'] = $setup_intent;
        $json['stripe_payment_context']['payment']['setup_intent_client_secret'] = $setup_intent_client_secret;



        $this->subscriptionPageLoader->subscriptionRepository->update([
                [
                    'id' => $subscriptionId,
                    'customFields' => $json
                ]
        ], $context->getContext());

        return $this->redirectToRoute('frontend.account.subscription.page');


    }

    private function getCards($subscription) {

        $stripe = new StripeClient($this->stripeSecretKey);
        $pms = null;

        $paymentDetails = null;
        // Stripe PI Payment
        if(!$pms && isset($subscription->getCustomFields()['stripe_payment_context']['payment']['payment_intent_id'])) {
            $pms = $stripe->paymentIntents->retrieve(
              $subscription->getCustomFields()['stripe_payment_context']['payment']['payment_intent_id'],
              []
            );


        }

        // Stripe setup intent Payment

        if(!$pms && isset($subscription->getCustomFields()['stripe_payment_context']['payment']['setup_intent'])) {

            $pms = $stripe->setupIntents->retrieve(
              $subscription->getCustomFields()['stripe_payment_context']['payment']['setup_intent'],
              []
            );

        }


        return $pms->charges->data[0]->payment_method_details;



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
        $subscription = $this->subscriptionPageLoader->subscriptionRepository->search($criteria, $salesChannelContext->getContext())->first();
        return $subscription;
    }

    private function getMailTemplate(SalesChannelContext $salesChannelContext, string $technicalName): ?MailTemplateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->setLimit(1);

        /** @var MailTemplateEntity|null $mailTemplate */
        $mailTemplate = $this->subscriptionPageLoader->mailTemplateRepository->search($criteria, $salesChannelContext->getContext())->first();
        return $mailTemplate;
    }


}