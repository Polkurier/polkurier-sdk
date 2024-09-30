<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;

class DeleteBankAccount extends AbstractMethod
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
        return 'delete_bank_account';
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
     * @param string|null $id
     * @return DeleteBankAccount
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param Response $response
     * @return DeleteBankAccount
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = $response->get('response');
        return $this;
    }
}
