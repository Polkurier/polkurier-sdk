<?php

namespace PolkurierWebServiceApi\Entities;

use JsonSerializable;

class OrderValuation implements JsonSerializable
{

    /**
     * @var string
     */
    private $servicecode = '';

    /**
     * @var string
     */
    private $serviceName = '';

    /**
     * @var int
     */
    private $netprice = 0;

    /**
     * @var int
     */
    private $grossprice = 0;

    /**
     * @var float
     */
    private $conditional_price_nett = 0.0;

    /**
     * @var float
     */
    private $conditional_price_gross = 0.0;

    /**
     * @var float
     */
    private $promotion_nett = 0.0;

    /**
     * @var float
     */
    private $promotion_gross = 0.0;

    /**
     * @var float
     */
    private $rebate_nett = 0.0;

    /**
     * @var float
     */
    private $rebate_gross = 0.0;

    /**
     * @var bool
     */
    private $available = true;

    /**
     * @var string
     */
    private $unavailable_message = '';

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
     * @return float
     */
    public function getGrossPrice()
    {
        return $this->grossprice;
    }

    /**
     * @param float $grossprice
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
     * @return float
     */
    public function getNetPrice()
    {
        return $this->netprice;
    }

    /**
     * @param float $netprice
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
     * @return OrderValuation
     */
    public function setConditionalPriceNett($conditional_price_nett)
    {
        $this->conditional_price_nett = (float)$conditional_price_nett;
        return $this;
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
        return $this;
    }

    /**
     * @return float
     */
    public function getPromotionNett()
    {
        return $this->promotion_nett;
    }

    /**
     * @param float $promotion_nett
     * @return OrderValuation
     */
    public function setPromotionNett($promotion_nett)
    {
        $this->promotion_nett = (float)$promotion_nett;
        return $this;
    }

    /**
     * @return float
     */
    public function getPromotionGross()
    {
        return $this->promotion_gross;
    }

    /**
     * @param float $promotion_gross
     * @return OrderValuation
     */
    public function setPromotionGross($promotion_gross)
    {
        $this->promotion_gross = (float)$promotion_gross;
        return $this;
    }

    /**
     * @return float
     */
    public function getRebateNett()
    {
        return $this->rebate_nett;
    }

    /**
     * @param float $rebate_nett
     * @return OrderValuation
     */
    public function setRebateNett($rebate_nett)
    {
        $this->rebate_nett = (float)$rebate_nett;
        return $this;
    }

    /**
     * @return float
     */
    public function getRebateGross()
    {
        return $this->rebate_gross;
    }

    /**
     * @param float $rebate_gross
     * @return OrderValuation
     */
    public function setRebateGross($rebate_gross)
    {
        $this->rebate_gross = (float)$rebate_gross;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * @param bool $available
     * @return OrderValuation
     */
    public function setAvailable($available)
    {
        $this->available = (bool)$available;
        return $this;
    }

    /**
     * @return string
     */
    public function getUnavailableMessage()
    {
        return $this->unavailable_message;
    }

    /**
     * @param string $unavailable_message
     * @return OrderValuation
     */
    public function setUnavailableMessage($unavailable_message)
    {
        $this->unavailable_message = (string)$unavailable_message;
        return $this;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'servicecode' => $this->getServiceCode(),
            'serviceName' => $this->getServiceName(),
            'netprice' => $this->getNetPrice(),
            'grossprice' => $this->getGrossPrice(),
            'conditional_price_nett' => $this->getConditionalPriceNett(),
            'conditional_price_gross' => $this->getConditionalPriceGross(),
            'promotion_nett' => $this->getPromotionNett(),
            'promotion_gross' => $this->getPromotionGross(),
            'rebate_nett' => $this->getRebateNett(),
            'rebate_gross' => $this->getRebateGross(),
            'available' => $this->isAvailable(),
            'unavailable_message' => $this->getUnavailableMessage(),
        ];
    }

}
