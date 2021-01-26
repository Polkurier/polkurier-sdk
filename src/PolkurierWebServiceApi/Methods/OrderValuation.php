<?php
namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Entities\Sender;
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
     * @var string
     */
    private $codtype = '';

    /**
     * @var string
     */
    private $return_cod = '';

    /**
     * @var float
     */
    private $insurance = 0;

    /**
     * @var Recipient|null
     */
    private $recipient;

    /**
     * @var Sender|null
     */
    private $sender;


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
     * @return string
     */
    public function getCodtype()
    {
        return $this->codtype;
    }

    /**
     * @param $codtype
     * @return $this
     */
    public function setCodtype($codtype)
    {
        $this->codtype = $codtype;
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnCod()
    {
        return $this->return_cod;
    }

    /**
     * @param $return_cod
     * @return $this
     */
    public function setReturnCod($return_cod)
    {
        $this->return_cod = $return_cod;
        return $this;
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
     * @return $this
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * @return Sender|null
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param Sender|null $sender
     * @return $this
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'returnvaluations' => $this->getReturnValuations(),
            'postcode_sender' => $this->sender ? $this->sender->getPostcode() : '',
            'postcode_recipient' => $this->recipient ? $this->recipient->getPostcode() : '',
            'recipient_country' => $this->recipient ? $this->recipient->getCountry() : '',
            'recipient_email' => $this->recipient ? $this->recipient->getEmail() : '',
            'shipmenttype' => $this->getShipmentType(),
            'packs' => array_map(function (Pack $pack) {
                return $pack->toArray();
            }, $this->packs),
            'COD' => $this->getCOD(),
            'codtype' => $this->getCodtype(),
            'return_cod' => $this->getReturnCod(),
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