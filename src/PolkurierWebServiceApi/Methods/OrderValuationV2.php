<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\OrderValuation as Valuation;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class OrderValuationV2 extends AbstractOrderMethod
{
    /**
     * @var string
     */
    private $returnValuations = '';

    /**
     * @return string
     */
    public function getName()
    {
        return 'order_valuation_v2';
    }

    /**
     * @param string $returnValuations
     * @return OrderValuationV2
     */
    public function setReturnValuations($returnValuations)
    {
        $this->returnValuations = $returnValuations;
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = parent::getRequestData();
        $data['returnvaluations'] = $this->returnValuations;
        return $data;
    }

    /**
     * @return OrderValuationV2
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
