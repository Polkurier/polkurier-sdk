<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Entities\BankAccount;
use PolkurierWebServiceApi\Util\Arr;

class SaveBankAccount extends AbstractMethod
{
    /**
     * @var array
     */
    private $accountData = [];

    /**
     * @return string
     */
    public function getName()
    {
        return 'save_bank_account';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        return $this->accountData;
    }

    /**
     * @param BankAccount $account
     * @return $this
     */
    public function setAccountData(BankAccount $account)
    {
        $this->accountData = $account->toArray();
        return $this;
    }

    /**
     * @param Response $response
     * @return BankAccount
     */
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
