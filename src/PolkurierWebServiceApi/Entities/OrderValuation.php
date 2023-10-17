<?php

namespace PolkurierWebServiceApi\Entities;

/**
 * Class OrderValuation
 * @package PolkurierWebServiceApi\Entities
 *
 */
class OrderValuation implements \JsonSerializable
{
    private $servicecode = '';
    private $serviceName = '';
    private $netprice = 0;
    private $grossprice = 0;
    private $conditional_price_nett = 0.0;
    private $conditional_price_gross = 0.0;

    /**
     * @return string
     */
    public function getServiceCode()
    {
        return $this->servicecode;
    }

    /**
     * @param string $servicecode
     * @return OrderValuation
     */
    public function setServiceCode($servicecode)
    {
        $this->servicecode = (string)$servicecode;
        return $this;
    }

    /**
     * @return int
     */
    public function getGrossPrice()
    {
        return $this->grossprice;
    }

    /**
     * @param int $grossprice
     * @return OrderValuation
     */
    public function setGrossPrice($grossprice)
    {
        $this->grossprice = (float)$grossprice;
        return $this;
    }

    /**
     * @return string
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @param string $serviceName
     * @return OrderValuation
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = (string)$serviceName;
        return $this;
    }

    /**
     * @return int
     */
    public function getNetPrice()
    {
        return $this->netprice;
    }

    /**
     * @param int $netprice
     * @return OrderValuation
     */
    public function setNetPrice($netprice)
    {
        $this->netprice = (float)$netprice;
        return $this;
    }

    /**
     * @return float
     */
    public function getConditionalPriceNett()
    {
        return $this->conditional_price_nett;
    }

    /**
     * @param float $conditional_price_nett
     */
    public function setConditionalPriceNett($conditional_price_nett)
    {
        $this->conditional_price_nett = (float)$conditional_price_nett;
    }

    /**
     * @return float
     */
    public function getConditionalPriceGross()
    {
        return $this->conditional_price_gross;
    }

    /**
     * @param float $conditional_price_gross
     */
    public function setConditionalPriceGross($conditional_price_gross)
    {
        $this->conditional_price_gross = (float)$conditional_price_gross;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'servicecode' => $this->getServiceCode(),
            'serviceName' => $this->getServiceName(),
            'netprice' => $this->getNetPrice(),
            'grossprice' => $this->getGrossPrice(),
            'conditional_price_nett' => $this->getConditionalPriceNett(),
            'conditional_price_gross' => $this->getConditionalPriceGross(),
        ];
    }
}
