<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Entities\BankAccount;
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

    public function setAccountData(BankAccount $account)
    {
        $this->accountData = $account->toArray();
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $account = new BankAccount();
        $account->setId(Arr::get($data, 'id'))
            ->setNumber(Arr::get($data, 'number'))
            ->setName(Arr::get($data, 'name'))
            ->setIsDefault(Arr::get($data, 'default'));

        return $account;
    }
}
