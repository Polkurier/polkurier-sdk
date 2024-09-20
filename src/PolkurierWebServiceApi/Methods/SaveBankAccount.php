<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Entities\BankAccount;
use PolkurierWebServiceApi\Util\Arr;

class SaveBankAccount extends AbstractMethod
{
    /**
     * @param BankAccount $bankAccount
     */
    public function __construct(BankAccount $bankAccount)
    {
        $this->setRequestData($bankAccount->jsonSerialize());
    }

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
        return $this->requestData;
    }

    /**
     * @param Response $response
     * @return $this
     */
    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $account = new BankAccount();
        $account->setId(Arr::get($data, 'id'))
            ->setNumber(Arr::get($data, 'number'))
            ->setName(Arr::get($data, 'name'))
            ->setIsDefault(Arr::get($data, 'default'));

        $this->responseData = $account;

        return $this;
    }
}
