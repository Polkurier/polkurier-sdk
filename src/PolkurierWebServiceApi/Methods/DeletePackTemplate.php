<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

class DeletePackTemplate extends AbstractMethod
{
    private $id;

    public function getName()
    {
        return 'delete_pack_template';
    }

    public function getRequestData()
    {
        return [
            'id' => $this->id,
        ];
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = $response->get('response');
        return $this;
    }
}
