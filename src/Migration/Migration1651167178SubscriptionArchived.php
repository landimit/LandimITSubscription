<?php declare(strict_types=1);

namespace LandimIT\Subscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1651167178SubscriptionArchived extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1651167178;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            ALTER TABLE `landimit_subscription` ADD COLUMN `archived` TINYINT(1) NULL DEFAULT '0' AFTER `active`;
        SQL;

        
        
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
