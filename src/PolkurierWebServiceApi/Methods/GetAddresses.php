<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetAddresses extends AbstractMethod
{
    private $type;

    public function getName()
    {
        return 'get_addresses';
    }

    public function getRequestData()
    {
        $data = [];
        if ($this->type) {
            $data['type'] = $this->type;
        }
        return $data;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        foreach ($response->get('response') as $row) {
            $address = [
                'id' => Arr::get($row, 'id'),
                'name' => Arr::get($row, 'name'),
                'company' => Arr::get($row, 'company'),
                'person' => Arr::get($row, 'person'),
                'city' => Arr::get($row, 'city'),
                'postcode' => Arr::get($row, 'postcode'),
                'street' => Arr::get($row, 'street'),
                'housenumber' => Arr::get($row, 'housenumber'),
                'flatnumber' => Arr::get($row, 'flatnumber'),
                'email' => Arr::get($row, 'email'),
                'phone' => Arr::get($row, 'phone'),
                'country' => Arr::get($row, 'country'),
                'province' => Arr::get($row, 'province'),
                'type' => Arr::get($row, 'type'),
                'default' => Arr::get($row, 'default'),
                'is_cover_address' => Arr::get($row, 'is_cover_address'),
            ];

            $this->responseData[] = $address;
        }
        return $this;
    }
}
