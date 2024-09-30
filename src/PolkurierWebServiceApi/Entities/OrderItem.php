<?php

namespace PolkurierWebServiceApi\Entities;

use PolkurierWebServiceApi\Type\OrderItemType;

class OrderItem implements \JsonSerializable
{

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var float
     */
    private $price;

    /**
     * @var float
     */
    private $valueNett;

    /**
     * @var float
     */
    private $valueGross;

    /**
     * @var string string
     */
    private $type;

    /**
     * @param string $description
     * @param int $amount
     * @param float $price
     * @param float $valueNett
     * @param float $valueGross
     * @param string $type
     */
    public function __construct(
        $description = '',
        $amount = 0,
        $price = 0.0,
        $valueNett = 0.0,
        $valueGross = 0.0,
        $type = OrderItemType::SERVICE
    )
    {
        $this->description = (string)$description;
        $this->amount = (int)$amount;
        $this->price = (float)$price;
        $this->valueNett = (float)$valueNett;
        $this->valueGross = (float)$valueGross;
        $this->type = (string)$type;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return float
     */
    public function getValueNett()
    {
        return $this->valueNett;
    }

    /**
     * @return float
     */
    public function getValueGross()
    {
        return $this->valueGross;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'description' => $this->description,
            'amount' => $this->amount,
            'price' => $this->price,
            'value_nett' => $this->valueNett,
            'value_gross' => $this->valueGross,
            'type' => $this->type
        ];
    }

}
