<?php

namespace PolkurierWebServiceApi\Entities;

class OrderItem implements \JsonSerializable
{
    private $description;
    private $amount;
    private $price;
    private $valueNett;
    private $valueGross;
    private $type;

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $price
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $valueNett
     * @return $this
     */
    public function setValueNett($valueNett)
    {
        $this->valueNett = $valueNett;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValueNett()
    {
        return $this->valueNett;
    }

    /**
     * @param float $valueGross
     * @return $this
     */
    public function setValueGross($valueGross)
    {
        $this->valueGross = $valueGross;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getValueGross()
    {
        return $this->valueGross;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
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
