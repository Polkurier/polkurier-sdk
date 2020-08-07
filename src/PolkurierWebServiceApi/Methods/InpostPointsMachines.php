<?php
namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;


/**
 * Class InpostPointsMachines
 * @package PolkurierWebServiceApi\Methods
 */
class InpostPointsMachines extends AbstractMethod
{


    /**
     * @return string
     */
    public function getName()
    {
        return 'inpost_points_machines",';
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
