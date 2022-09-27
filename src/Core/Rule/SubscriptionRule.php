<?php declare(strict_types=1);

namespace LandimIT\Subscription\Core\Rule;

use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraints\Type;

class SubscriptionRule extends Rule
{
    protected bool $isSubscription; // 'protected' is very important here!

    public function __construct()
    {
        parent::__construct();

        // Will be overwritten at runtime. Reflects the expected value.
        $this->isSubscription = false;
    }

    public function getName(): string
    {
        return 'subscription';
    }

    public function match(RuleScope $scope): bool
    {
        $isSubscription = $this->checkSubscription($scope);

        // Checks if the shop owner set the rule to "Subscription => Yes"
        if ($this->isSubscription) {
            // Shop administrator wants the rule to match if there's currently a subscription.
            return $isSubscription;
        }

        // Shop administrator wants the rule to match if there's currently NOT a subscription.
        return !$isSubscription;
    }    

    private function checkSubscription(RuleScope $scope): bool 
    {
        foreach ($scope->getCart()->getLineItems() as $item) {
            if(isset($item->getExtensions()['subscription']) && $item->getExtensions()['subscription']->getInterval() > 0)
                return true;

        }
        return false;
    }


    public function getConstraints(): array
    {
        return [
            'isSubscription' => [ new Type('bool') ]
        ];
    }
}