<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

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
     * @param Response $response
     * @return GetCountries
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = $response->get('response');
        return $this;
    }

}
