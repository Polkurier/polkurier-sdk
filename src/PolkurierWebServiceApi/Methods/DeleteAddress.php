<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

class DeleteAddress extends AbstractMethod
{
    private $id;
    private $type;

    public function getName()
    {
        return 'delete_address';
    }

    public function getRequestData()
    {
        return [
            'id' => $this->id,
            'type' => $this->type
        ];
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
