<?php

namespace PolkurierWebServiceApi\Methods;

class DeleteAddress extends AbstractMethod
{

    /**
     * @var string
     */
    private $id = '';

    /**
     * @var string
     */
    private $type = '';

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
     * @return DeleteAddress
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string|null $type
     * @return DeleteAddress
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

}
