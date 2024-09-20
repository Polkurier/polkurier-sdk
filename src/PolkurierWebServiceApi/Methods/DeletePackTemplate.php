<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

class DeletePackTemplate extends AbstractMethod
{
    /**
     * @var
     */
    private $id;

    /**
     * @return string
     */
    public function getName()
    {
        return 'delete_pack_template';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'id' => $this->id,
        ];
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param Response $response
     * @return $this|DeletePackTemplate
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = $response->get('response');
        return $this;
    }
}
