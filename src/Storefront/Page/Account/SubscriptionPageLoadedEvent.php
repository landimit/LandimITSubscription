<?php declare(strict_types=1);

namespace LandimIT\Subscription\Storefront\Page\Account;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;
use LandimIT\Subscription\Storefront\Page\Account\SubscriptionPage;

class SubscriptionPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var ExamplePage
     */
    protected $page;

    public function __construct(SubscriptionPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): SubscriptionPage
    {
        return $this->page;
    }
}