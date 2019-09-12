<?php
namespace PolkurierWebServiceApi\Entities;
use PolkurierWebServiceApi\Exception\ErrorException;
use PolkurierWebServiceApi\Type\PackType;

/**
 * Class Pack
 * @package PolkurierWebServiceApi\Entities
 *
 */
class Pack
{
    /**
     * @var float
     */
    private $length = 0;

    /**
     * @var float
     */
    private $width = 0;

    /**
     * @var float
     */
    private $height = 0;

    /**
     * @var float
     */
    private $weight = 0;

    /**
     * @var float
     */
    private $amount = 1;

    /**
     * @var string
     */
    private $type = PackType::ST;

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param $length
     * @return $this
     * @throws \PolkurierWebServiceApi\Exception\ErrorException
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
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param mixed $width
     * @return Pack
     * @throws ErrorException
     */
    public function setWidth($width)
    {
        if ($width <= 0) {
            throw new ErrorException('Szerokość przesyłki musi być większa niż 0');
        }
        $this->width = (float)$width;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param mixed $height
     * @return Pack
     * @throws ErrorException
     */
    public function setHeight($height)
    {
        if ($height <= 0) {
            throw new ErrorException('Wysokość przesyłki musi być większa niż 0');
        }
        $this->height = (float)$height;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param mixed $weight
     * @return Pack
     * @throws ErrorException
     */
    public function setWeight($weight)
    {
        if ($weight <= 0) {
            throw new ErrorException('Waga przesyłki musi być większa niż 0');
        }
        $this->weight = (float) $weight;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return Pack
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
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