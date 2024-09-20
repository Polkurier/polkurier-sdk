<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

class DeleteAddress extends AbstractMethod
{
    /**
     * @var
     */
    private $id;
    /**
     * @var
     */
    private $type;

    /**
     * @return string
     */
    public function getName()
    {
        return 'delete_address';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [
            'id' => $this->id,
            'type' => $this->type
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
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
