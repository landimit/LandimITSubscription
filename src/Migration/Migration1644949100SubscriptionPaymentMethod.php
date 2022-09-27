<?php declare(strict_types=1);

namespace LandimIT\Subscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1644949100SubscriptionPaymentMethod extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1644949100;
    }

    public function update(Connection $connection): void
    {

        
        $sql = <<<SQL
        ALTER TABLE `landimit_subscription` ADD COLUMN `payment_method_id` BINARY(16) NULL AFTER `discount`;    
        ALTER TABLE `landimit_subscription` ADD CONSTRAINT `fk.landimit_subscription.payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE CASCADE ON UPDATE CASCADE; 
        ALTER TABLE `landimit_subscription` ADD INDEX `fk.landimit_subscription.payment_method_id` (`payment_method_id`); 
        UPDATE `landimit_subscription` SET payment_method_id = (SELECT id from payment_method where handler_identifier = 'stripe.shopware_payment.payment_handler.card');
        ALTER TABLE `landimit_subscription` CHANGE COLUMN `payment_method_id` `payment_method_id` BINARY(16) NOT NULL AFTER `discount`;
        SQL;

        
        
        $connection->executeStatement($sql);


        


    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
