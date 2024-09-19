<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\BankAccount;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetBankAccounts extends AbstractMethod
{
    public function getName()
    {
        return 'get_bank_accounts';
    }

    public function getRequestData()
    {
        return [];
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        foreach ($response->get('response') as $row) {
            $account = new BankAccount();
            $account->setId($account['id']);
            $account->setNumber($account['number']);
            $account->setName($account['name']);
            $account->setIsDefault($account['default']);

            $this->responseData[] = $account;
        }
        return $this;
    }
}
