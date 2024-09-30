<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

class DeletePackTemplate extends AbstractMethod
{

    /**
     * @var string
     */
    private $id = '';

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
     * @param string $id
     * @return DeletePackTemplate
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param Response $response
     * @return DeletePackTemplate
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = $response->get('response');
        return $this;
    }

}
