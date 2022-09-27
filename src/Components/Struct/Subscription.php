<?php

declare(strict_types=1);

namespace LandimIT\Subscription\Components\Struct;

use Shopware\Core\Framework\Struct\Struct;

class Subscription extends Struct
{

    /** @var int */
    protected $interval;
    protected $name;
    
    /**
     * @throws InvalidQuantityException
     */
    public function __construct(int $interval)
    {
        $this->interval = $interval;
    }

    
    public function getInterval(): int
    {
        return $this->interval;
    }

    public function setInterval($interval): int
    {
        $this->interval = $interval;
    }


    public function getName(): string
    {
        $week = 60*60*24*7;

        switch($this->interval) {
            case (2*$week):
                $this->name = '2weeks';
                break;
            case (4*$week):
                $this->name = '4weeks';
                break;
            case (6*$week):
                $this->name = '6weeks';
                break;
            case (8*$week):
                $this->name = '8weeks';
                break;
            default:
                $this->name = 'Onetimepurchase';
                break;
        }


        return $this->name;
    }
}
