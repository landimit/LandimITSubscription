<?php declare(strict_types=1);

namespace LandimIT\Subscription\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\addFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SubscriptionCreateCommand extends Command
{
    // Command name
    protected static $defaultName = 'landimit:subscription:create';

    public function __construct(
            EntityRepositoryInterface $subscriptionEntity,
            EntityRepositoryInterface $customerRepository,
            EntityRepositoryInterface $productRepository,
            EntityRepositoryInterface $salesChannelRepository,
            EntityRepositoryInterface $salesChannelTypeRepository
    ) {
        parent::__construct();

        $this->subscriptionEntity = $subscriptionEntity;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->salesChannelTypeRepository = $salesChannelTypeRepository;

    }

    // Provides a description, printed out in bin/console
    protected function configure(): void
    {
        $this->setName('landimit:subscription:create')
            ->setDescription('Create subscription.')
            ->addArgument('customer', InputArgument::REQUIRED, 'customer')
            ->addArgument('product', InputArgument::REQUIRED, 'product')
            ->addArgument('salesChannel', InputArgument::REQUIRED, 'sales_channel');
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {



        $id = Uuid::randomHex();
        $customerId = (string) $input->getArgument('customer');
        $productId = (string) $input->getArgument('product');
        $salesChannelId = (string) $input->getArgument('salesChannel');


        $customer = $this->customerRepository->search(new Criteria([$customerId]), Context::createDefaultContext())->first();
        $product = $this->productRepository->search(new Criteria([$productId]), Context::createDefaultContext())->first();
        $salesChannel = $this->getSalesChannel();

        $this->subscriptionEntity->create([
                [
                    'id' => $id,
                    'customerId' => $customer->getId(),
                    'productId' => $product->getId(),
                    'salesChannelId' => $salesChannel->getId(),
                    'quantity' => 1,
                    'interval' => 1800,
                    'stripeTk' => 'xxxx',
                    'lastRenew' => new \DateTime("NOW"),
                    'nextRenew' => new \DateTime("NOW")
                ]
        ], Context::createDefaultContext());

        return 0;
    }

    private function getSalesChannel() {

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('iconName', 'default-building-shop')
        );

        $salesChannelTypeList = $this->salesChannelTypeRepository->search($criteria, Context::createDefaultContext());
        $salesChannelType = $salesChannelTypeList->first();


        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('typeId', $salesChannelType->getId())
        );


        $salesChannelList= $this->salesChannelRepository->search($criteria, Context::createDefaultContext());


        return $salesChannelList->first();
    }

}