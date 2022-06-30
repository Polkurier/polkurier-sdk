<?php


namespace PolkurierWebServiceApi\Methods;


use PolkurierWebServiceApi\Response;

class PocztexPostOffices extends AbstractMethod
{

    /**
     * @return string
     */
    public function getName()
    {
        return 'pocztex_post_offices';
    }


    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
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
