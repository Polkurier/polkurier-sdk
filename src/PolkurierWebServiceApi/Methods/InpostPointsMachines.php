<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;


/**
 * @deprecated Use method GetCourierPoint
 */
class InpostPointsMachines extends AbstractMethod
{

    /**
     * @return string
     */
    public function getName()
    {
        return 'inpost_points_machines';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }

    /**
     * @return InpostPointsMachines
     */
    public function setResponseData(Response $response)
    {

        $this->responseData = $response->get('response');
        return $this;
    }

}
