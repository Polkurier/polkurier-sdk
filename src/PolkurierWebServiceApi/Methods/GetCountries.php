<?php
namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

/**
 * Class GetCountries
 * @package PolkurierWebServiceApi\Methods
 *
 */
class GetCountries extends AbstractMethod
{
    /**
     * @var string
     */
    private $couriers;

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_countries';
    }

    /**
     * @return string
     */
    private function getCouriers()
    {
        return $this->couriers;
    }

    /**
     * @param string $couriers
     * @return GetCountries
     */
    public function setCouriers($couriers)
    {
        $this->couriers = $couriers;
        return $this;
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'couriers' => $this->getCouriers()
        ];
    }

    /**
     * @param \PolkurierWebServiceApi\Response $response
     * @return $this|\PolkurierWebServiceApi\Methods\AbstractMethod
     */
    public function setResponseData(Response $response) {

        $this->responseData = $response->get('response');
        return $this;
    }
}
