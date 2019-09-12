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
        $this->servicecode = (string) $servicecode;
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
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'servicecode' => $this->getServiceCode(),
            'serviceName' => $this->getServiceName(),
            'netprice' => $this->getNetPrice(),
            'grossprice' => $this->getGrossPrice(),
        ];
    }
}