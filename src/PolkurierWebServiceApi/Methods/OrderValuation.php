<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\CourierServiceInterface;
use PolkurierWebServiceApi\Entities\OrderValuation as Valuation;
use PolkurierWebServiceApi\Entities\Pack;
use PolkurierWebServiceApi\Entities\Recipient;
use PolkurierWebServiceApi\Entities\Sender;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

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
    private $COD = 0.0;

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
    private $insurance = 0.0;

    /**
     * @var Recipient|null
     */
    private $recipient;

    /**
     * @var Sender|null
     */
    private $sender;

    /**
     * @var array
     */
    private $courierservice = [];

    /**
     * @return string
     */
    public function getName()
    {
        return 'order_valuation';
    }

    /**
     * @param string $returnValuations
     * @return OrderValuation
     */
    public function setReturnValuations($returnValuations)
    {
        $this->returnValuations = $returnValuations;
        return $this;
    }

    /**
     * @return OrderValuation
     */
    public function addPack(Pack $pack)
    {
        $this->packs[] = $pack;
        return $this;
    }

    /**
     * @param string $shipmentType
     * @return OrderValuation
     */
    public function setShipmentType($shipmentType)
    {
        $this->shipmentType = (string)$shipmentType;
        return $this;
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
     * @param string $codtype
     * @return OrderValuation
     */
    public function setCodtype($codtype)
    {
        $this->codtype = $codtype;
        return $this;
    }

    /**
     * @param string $return_cod
     * @return OrderValuation
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
     * @param Recipient|null $recipient
     * @return OrderValuation
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * @param Sender|null $sender
     * @return OrderValuation
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @return OrderValuation
     */
    public function addCourierService(CourierServiceInterface $courierservice)
    {
        $this->courierservice[] = $courierservice;
        return $this;
    }

    /**
     * @return array
     */
    private function getCourierServiceMap()
    {
        $servicemap = [];
        foreach ($this->courierservice as $item) {
            $itemarray = $item->toArray();
            $servicemap[key($itemarray)] = current($itemarray);
        }
        return $servicemap;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'returnvaluations' => $this->returnValuations,
            'postcode_sender' => $this->sender ? $this->sender->getPostcode() : '',
            'postcode_recipient' => $this->recipient ? $this->recipient->getPostcode() : '',
            'recipient_country' => $this->recipient ? $this->recipient->getCountry() : '',
            'recipient_email' => $this->recipient ? $this->recipient->getEmail() : '',
            'shipmenttype' => $this->shipmentType,
            'packs' => $this->packs,
            'COD' => $this->COD,
            'codtype' => $this->codtype,
            'return_cod' => $this->return_cod,
            'insurance' => $this->insurance,
            'courierservice' => $this->getCourierServiceMap(),
        ];
    }

    /**
     * @return OrderValuation
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        $rawData = $response->get('response');

        if ($this->returnValuations) {
            $rawData = [$rawData];
        }

        foreach ($rawData as $row) {
            $item = new Valuation();
            $item->setGrossPrice(Arr::get($row, 'grossprice'));
            $item->setNetPrice(Arr::get($row, 'netprice'));
            $item->setConditionalPriceGross(Arr::get($row, 'conditional_price_gross'));
            $item->setConditionalPriceNett(Arr::get($row, 'conditional_price_nett'));
            $item->setServiceCode(Arr::get($row, 'servicecode'));
            $item->setServiceName(Arr::get($row, 'serviceName'));
            $item->setPromotionNett(Arr::get($row, 'promotion_nett'));
            $item->setPromotionGross(Arr::get($row, 'promotion_gross'));
            $item->setRebateNett(Arr::get($row, 'rebate_nett'));
            $item->setRebateGross(Arr::get($row, 'rebate_gross'));
            $item->setAvailable(Arr::get($row, 'available', true));
            $item->setUnavailableMessage(Arr::get($row, 'unavailable_message', ''));
            $this->responseData[] = $item;
        }
        return $this;
    }

}
