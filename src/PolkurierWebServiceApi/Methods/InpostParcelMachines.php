<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

/**
 * Class InpostParcelMachines
 * @package PolkurierWebServiceApi\Methods
 */
class InpostParcelMachines extends AbstractMethod
{
    /**
     * @var bool
     */
    private $cod_available = false;

    /**
     * @var bool
     */
    private $parcel_send = false;

    /**
     * @return string
     */
    public function getName()
    {
        return 'inpost_parcel_machines';
    }

    /**
     * @return bool
     */
    private function isCodAvailable()
    {
        return $this->cod_available;
    }

    /**
     * @param bool $cod_available
     * @return InpostParcelMachines
     */
    public function setCodAvailable($cod_available)
    {
        $this->cod_available = $cod_available;
        return $this;
    }

    /**
     * @return bool
     */
    private function isParcelSend()
    {
        return $this->parcel_send;
    }

    /**
     * @param bool $parcel_send
     * @return InpostParcelMachines
     */
    public function setParcelSend($parcel_send)
    {
        $this->parcel_send = $parcel_send;
        return $this;
    }


    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'cod_available' => $this->isCodAvailable(),
            'parcel_send' => $this->isParcelSend()
        ];
    }


    /**
     * @param Response $response
     * @return $this|AbstractMethod
     */
    public function setResponseData(Response $response)
    {

        $this->responseData = $response->get('response');
        return $this;
    }
}
