<?php declare(strict_types=1);

namespace LandimIT\Subscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1637178806Subscription extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1637178806;
    }

    public function update(Connection $connection): void
    {
        // implement update
        
        $sql = <<<SQL
            CREATE TABLE `landimit_subscription` (
                `id` BINARY(16) NOT NULL,
                `auto_increment` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `customer_id` BINARY(16) NULL,
                `currency_id` BINARY(16) NULL,
                `sales_channel_id` BINARY(16) NULL,
                `interval` INT(11) NOT NULL,
                `last_renew` DATETIME(3) NULL,
                `next_renew` DATETIME(3) NULL,
                `active` TINYINT(1) NULL DEFAULT '0',
                `custom_fields` JSON NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                `total_price` DOUBLE NULL,
                `discount` DOUBLE NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.auto_increment` (`auto_increment`),
                CONSTRAINT `json.landimit_subscription.custom_fields` CHECK (JSON_VALID(`custom_fields`)),
                KEY `fk.landimit_subscription.customer_id` (`customer_id`),
                KEY `fk.landimit_subscription.sales_channel_id` (`sales_channel_id`),
                KEY `fk.landimit_subscription.currency_id` (`currency_id`),
                CONSTRAINT `fk.landimit_subscription.customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.landimit_subscription.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.landimit_subscription.currency_id` FOREIGN KEY (`currency_id`) REFERENCES `currency` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=7005 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



            CREATE TABLE `landimit_subscription_line_item` (
                `id` BINARY(16) NOT NULL,
                `subscription_id` BINARY(16) NULL,
                `product_id` BINARY(16) NULL,
                `quantity` INT(11) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                `unit_price` DOUBLE NULL,
                `total_price` DOUBLE NULL,
                `discount` DOUBLE NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.landimit_subscription_line_item.product_id` (`product_id`,`product_version_id`),
                KEY `fk.landimit_subscription_line_item.subscription_id` (`subscription_id`),
                CONSTRAINT `fk.landimit_subscription_line_item.product_id` FOREIGN KEY (`product_id`,`product_version_id`) REFERENCES `product` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.landimit_subscription_line_item.subscription_id` FOREIGN KEY (`subscription_id`) REFERENCES `landimit_subscription` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE `landimit_subscription_order` (
                `id` BINARY(16) NOT NULL,
                `subscription_id` BINARY(16) NULL,
                `order_id` BINARY(16) NULL,
                `order_version_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                KEY `fk.landimit_subscription_order.order_id` (`order_id`,`order_version_id`),
                KEY `fk.landimit_subscription_order.subscription_id` (`subscription_id`),
                CONSTRAINT `fk.landimit_subscription_order.order_id` FOREIGN KEY (`order_id`,`order_version_id`) REFERENCES `order` (`id`,`version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.landimit_subscription_order.subscription_id` FOREIGN KEY (`subscription_id`) REFERENCES `landimit_subscription` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

           
        SQL;
        
        $connection->executeStatement($sql);
        
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}



