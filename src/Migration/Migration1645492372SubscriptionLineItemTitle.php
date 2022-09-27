<?php declare(strict_types=1);

namespace LandimIT\Subscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1645492372SubscriptionLineItemTitle extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1645492372;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `landimit_subscription_line_item` ADD COLUMN `label` VARCHAR(255) NULL AFTER `product_id`;    
        UPDATE `landimit_subscription_line_item` wsli SET label = (SELECT oli.label from order_line_item oli, landimit_subscription_order wso where oli.order_id = wso.order_id and wsli.subscription_id = wso.subscription_id order by oli.label asc limit 1);
        ALTER TABLE `landimit_subscription_line_item` CHANGE COLUMN `label` `label` VARCHAR(255) NOT NULL AFTER `product_id`;    
        SQL;

        
        
        $connection->executeStatement($sql);
        

    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
