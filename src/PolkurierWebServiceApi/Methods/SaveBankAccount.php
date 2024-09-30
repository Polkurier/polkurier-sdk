<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\BankAccount;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class SaveBankAccount extends AbstractMethod
{

    public function __construct(BankAccount $bankAccount, $access_pin = '')
    {
        $this->requestData = $bankAccount->jsonSerialize();
        if ($access_pin !== '') {
            $this->requestData['access_pin'] = $access_pin;
        }
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
     * @return SaveBankAccount
     */
    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $this->responseData = (new BankAccount())
            ->setId((string)Arr::get($data, 'id', ''))
            ->setNumber((string)Arr::get($data, 'number', ''))
            ->setName((string)Arr::get($data, 'name', ''))
            ->setDefault((bool)Arr::get($data, 'default', false));
        return $this;
    }

}
