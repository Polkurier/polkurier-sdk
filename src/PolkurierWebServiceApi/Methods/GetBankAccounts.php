<?php

namespace PolkurierWebServiceApi\Methods;

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
            $account = [
                'id' => Arr::get($row, 'id'),
                'number' => Arr::get($row, 'number'),
                'name' => Arr::get($row, 'name'),
                'default' => Arr::get($row, 'default', false)
            ];
            $this->responseData[] = $account;
        }
        return $this;
    }
}
