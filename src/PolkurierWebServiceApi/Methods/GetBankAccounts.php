<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\BankAccount;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetBankAccounts extends AbstractMethod
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'get_bank_accounts';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return [];
    }

    /**
     * @param Response $response
     * @return $this
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        foreach ($response->get('response') as $row) {
            $account = new BankAccount();
            $account->setId(Arr::get($row, 'id'));
            $account->setNumber(Arr::get($row, 'number'));
            $account->setName(Arr::get($row, 'name'));
            $account->setIsDefault(Arr::get($row, 'default'));

            $this->responseData[] = $account;
        }
        return $this;
    }
}
