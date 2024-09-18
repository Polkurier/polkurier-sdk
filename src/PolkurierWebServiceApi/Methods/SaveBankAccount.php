<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class SaveBankAccount extends AbstractMethod
{
    private $accountData = [];

    public function getName()
    {
        return 'save_bank_account';
    }

    public function getRequestData()
    {
        return $this->accountData;
    }

    public function setAccountData(array $accountData)
    {
        $this->accountData = $accountData;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = $response->get('response');
        return $this;
    }
}
