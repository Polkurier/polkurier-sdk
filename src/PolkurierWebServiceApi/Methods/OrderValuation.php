<?php
namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;
use PolkurierWebServiceApi\Entities\OrderValuation as Valuation;
/**
 * Class OrderValuation
 * @package PolkurierWebServiceApi\Methods
 *
 */
class OrderValuation extends AbstractMethod
{
    /**
     * @var string
     */
    private $returnValuations = '';

    /**
     * @var string
     */
    private $shipmentType = '';
    
    /**
     * @var array
     */
    private $packs = [];

    /**
     * @var float
     */
    private $COD = 0;

    /**
     * @var float
     */
    private $insurance = 0;

    /**
     * @var Recipient|null
     */
    private $recipient;


    /**
     * @return string
     */
    public function getName()
    {
        return 'order_valuation';
    }

    /**
     * @param \PolkurierWebServiceApi\Entities\Pack $pack
     * @return $this
     */
    public function addPack(Pack $pack)
    {
        $this->packs[] = $pack;
        return $this;
    }

    /**
     * @param string $returnValuations
     * @return OrderValuation
     */
    public function setReturnValuations($returnValuations)
    {
        $this->returnValuations = (string) $returnValuations;
        return $this;
    }

    /**
     * @return string
     */
    private function getReturnValuations()
    {
        return $this->returnValuations;
    }


    /**
     * @param string $shipmentType
     * @return OrderValuation
     */
    public function setShipmentType($shipmentType)
    {
        $this->shipmentType = (string) $shipmentType;
        return $this;
    }

    /**
     * @return string
     */
    private function getShipmentType()
    {
        return $this->shipmentType;
    }

    /**
     * @param int $COD
     * @return OrderValuation
     */
    public function setCOD($COD)
    {
        $this->COD = $COD;
        return $this;
    }

    /**
     * @return float
     */
    private function getCOD()
    {
        return $this->COD;
    }


    /**
     * @param int $insurance
     * @return OrderValuation
     */
    public function setInsurance($insurance)
    {
        $this->insurance = $insurance;
        return $this;
    }

    /**
     * @return float
     */
    private function getInsurance()
    {
        return $this->insurance;
    }

    /**
     * @return Recipient|null
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param Recipient|null $recipient
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'returnvaluations' => $this->getReturnValuations(),
            'recipient' => $this->recipient ? [
                'country' => $this->recipient->getCountry()
            ] : null,
            'shipmenttype' => $this->getShipmentType(),
            'packs' => array_map(function (Pack $pack) {
                return $pack->toArray();
            }, $this->packs),
            'COD' => $this->getCOD(),
            'insurance' => $this->getInsurance()
        ];
    }

    /**
     * @param \PolkurierWebServiceApi\Response $response
     * @return $this|\PolkurierWebServiceApi\Methods\AbstractMethod
     */
    public function setResponseData(Response $response) {

        $this->responseData = [];

        $rawData = $response->get('response');

        if ($this->returnValuations) {
            $rawData = [$rawData];
        }

        foreach ($rawData as $row) {

            $item = new Valuation();
            $item->setGrossPrice(Arr::get($row, 'grossprice'));
            $item->setNetPrice(Arr::get($row, 'netprice'));
            $item->setServiceCode(Arr::get($row, 'servicecode'));
            $item->setServiceName(Arr::get($row, 'serviceName'));
            $this->responseData[] = $item;
        }
        return $this;
    }
}