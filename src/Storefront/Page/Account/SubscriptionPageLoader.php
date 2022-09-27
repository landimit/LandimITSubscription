<?php declare(strict_types=1);

namespace LandimIT\Subscription\Storefront\Page\Account;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use LandimIT\Subscription\Storefront\Page\Account\SubscriptionPage;
use LandimIT\Subscription\Storefront\Page\Account\SubscriptionPageLoadedEvent;


class SubscriptionPageLoader
{
    /**
     * @var GenericPageLoaderInterface
     */
    private $genericPageLoader;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EventDispatcherInterface
     */
    private $container;

    /**
     * @var ProductLoader
     */
    private $productLoader;

    public function __construct(
        GenericPageLoaderInterface $genericPageLoader,
        EventDispatcherInterface $eventDispatcher,
        EntityRepositoryInterface $subscriptionRepository,
        SystemConfigService $systemConfigService,
        AbstractMailService $mailService,
        EntityRepositoryInterface $mailTemplateRepository
    ) {
        $this->genericPageLoader = $genericPageLoader;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->systemConfigService = $systemConfigService;
        $this->mailService = $mailService;
        $this->mailTemplateRepository = $mailTemplateRepository;
    }

    public function load(Request $request, SalesChannelContext $context): SubscriptionPage
    {
        $page = $this->genericPageLoader->load($request, $context);
        $page = SubscriptionPage::createFrom($page);


        $this->eventDispatcher->dispatch(
            new SubscriptionPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }
}