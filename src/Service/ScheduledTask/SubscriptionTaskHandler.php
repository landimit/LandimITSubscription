<?php declare(strict_types=1);

namespace LandimIT\Subscription\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;

use Shopware\Core\System\SalesChannel\Context\CachedSalesChannelContextFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;

class SubscriptionTaskHandler extends ScheduledTaskHandler
{
    protected $productRepository;
    protected $scheduledTaskRepository;
    protected $salesProductRepository;
    protected $contextFactory;
    protected $categoryRepository;

    
    public function __construct(
        EntityRepositoryInterface $scheduled_task, 
        SalesChannelRepository $salesProductRepository, 
        EntityRepositoryInterface $salesDomainChannel, 
        CachedSalesChannelContextFactory $contextFactory,
        EntityRepositoryInterface $categoryRepository,
        ContainerInterface $container,
        EntityRepositoryInterface $salesChannel,
        EntityRepositoryInterface $subscriptionEntity,
        OrderService $orderService
    ) {

        $this->salesProductRepository = $salesProductRepository;
        $this->scheduledTaskRepository = $scheduled_task;
        $this->salesChannel = $salesChannel;
        $this->contextFactory = $contextFactory;
        $this->categoryRepository = $categoryRepository;
        $this->container = $container;
        $this->subscriptionEntity = $subscriptionEntity;
        $this->orderService = $orderService;

    }

    public static function getHandledMessages(): iterable
    {
        return [ SubscriptionTask::class ];
    }

    public function run(): void
    {


        $rootDir = $this->container->getParameter('kernel.project_dir');
        
        $searchResult = $this->subscriptionEntity->search(new Criteria(), Context::createDefaultContext());
        
       


    }






}