<?php declare(strict_types=1);

namespace LandimIT\Subscription\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class SubscriptionTask extends ScheduledTask
{




    public static function getTaskName(): string
    {
        return 'landimit.subscription_task';
    }

    public static function getDefaultInterval(): int
    {
        return 86400; // 60 minutes
    }
}