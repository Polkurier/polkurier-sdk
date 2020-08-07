<?php


namespace PolkurierWebServiceApi\Methods;


use PolkurierWebServiceApi\Response;

/**
 * Class Kurier48PostOffices
 * @package PolkurierWebServiceApi\Methods
 */
class Kurier48PostOffices extends AbstractMethod
{

    /**
     * @return string
     */
    public function getName()
    {
        return 'kurier48_post_offices';
    }


    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }


    /**
     * @param \PolkurierWebServiceApi\Response $response
     * @return $this|\PolkurierWebServiceApi\Methods\AbstractMethod
     */
    public function setResponseData(Response $response) {

        $this->responseData = $response->get('response') ;
        return $this;
    }
}
