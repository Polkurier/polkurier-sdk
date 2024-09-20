<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

class DeleteAddress extends AbstractMethod
{
    /**
     * @var string|null
     */
    private $id;
    /**
     * @var string|null
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
     * @param string|null $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string|null $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}
