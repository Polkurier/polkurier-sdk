<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Address;
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
            $address = new Address();
            $address->setId(Arr::get($row, 'id'))
                ->setName(Arr::get($row, 'name'))
                ->setCompany(Arr::get($row, 'company'))
                ->setPerson(Arr::get($row, 'person'))
                ->setCity(Arr::get($row, 'city'))
                ->setPostcode(Arr::get($row, 'postcode'))
                ->setStreet(Arr::get($row, 'street'))
                ->setHouseNumber(Arr::get($row, 'housenumber'))
                ->setFlatNumber(Arr::get($row, 'flatnumber'))
                ->setEmail(Arr::get($row, 'email'))
                ->setPhone(Arr::get($row, 'phone'))
                ->setCountry(Arr::get($row, 'country'))
                ->setProvince(Arr::get($row, 'province'))
                ->setType(Arr::get($row, 'type'))
                ->setDefault(Arr::get($row, 'default'))
                ->setIsCoverAddress(Arr::get($row, 'is_cover_address'));

            $this->responseData[] = $address;
        }

        return $this;
    }
}
