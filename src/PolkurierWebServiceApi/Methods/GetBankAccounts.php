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
     * @return GetBankAccounts
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        foreach ($response->get('response') as $row) {
            $this->responseData[] = (new BankAccount())
                ->setId((string)Arr::get($row, 'id'))
                ->setNumber((string)Arr::get($row, 'number'))
                ->setName((string)Arr::get($row, 'name'))
                ->setDefault((bool)Arr::get($row, 'default'));
        }
        return $this;
    }
}
