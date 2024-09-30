<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Type\PackType;

class Pack implements JsonSerializable
{

    /**
     * @var int
     */
    private $length = 0;

    /**
     * @var int
     */
    private $width = 0;

    /**
     * @var int
     */
    private $height = 0;

    /**
     * @var float
     */
    private $weight = 0;

    /**
     * @var int
     */
    private $amount = 1;

    /**
     * @var string
     */
    private $type = PackType::ST;

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     * @return Pack
     * @throws ErrorException
     */
    public function setLength($length)
    {
        if ($length <= 0) {
            throw new ErrorException('Długość przesyłki musi być większa niż 0');
        }
        $this->length = (float)$length;
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     * @return Pack
     * @throws ErrorException
     */
    public function setWidth($width)
    {
        if ($width <= 0) {
            throw new ErrorException('Szerokość przesyłki musi być większa niż 0');
        }
        $this->width = (int)$width;
        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param int $height
     * @return Pack
     * @throws ErrorException
     */
    public function setHeight($height)
    {
        if ($height <= 0) {
            throw new ErrorException('Wysokość przesyłki musi być większa niż 0');
        }
        $this->height = (int)$height;
        return $this;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     * @return Pack
     * @throws ErrorException
     */
    public function setWeight($weight)
    {
        if ($weight <= 0) {
            throw new ErrorException('Waga przesyłki musi być większa niż 0');
        }
        $this->weight = (float)$weight;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return Pack
     * @throws ErrorException
     */
    public function setAmount($amount)
    {
        if ($amount > 99 || $amount < 1) {
            throw new ErrorException('Ilość musi zawierać się w przedziale od 1 do 99');
        }
        $this->amount = (int)$amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Pack
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     * @deprecated
     */
    public function toArray()
    {
        return $this->jsonSerialize();
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight' => $this->weight,
            'amount' => $this->amount,
            'type' => $this->type
        ];
    }

}
