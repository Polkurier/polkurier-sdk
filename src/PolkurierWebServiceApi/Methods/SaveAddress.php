<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Entities\Address;
use PolkurierWebServiceApi\Util\Arr;

class SaveAddress extends AbstractMethod
{
    private $addressData = [];

    public function getName()
    {
        return 'save_address';
    }

    public function getRequestData()
    {
        return ['data' => $this->addressData];
    }

    public function setAddressData(Address $address)
    {
        $this->addressData = $address->toArray();
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $address = new Address();
        $address->setId(Arr::get($data, 'id'))
            ->setName(Arr::get($data, 'name'))
            ->setCompany(Arr::get($data, 'company'))
            ->setPerson(Arr::get($data, 'person'))
            ->setCity(Arr::get($data, 'city'))
            ->setPostcode(Arr::get($data, 'postcode'))
            ->setStreet(Arr::get($data, 'street'))
            ->setHouseNumber(Arr::get($data, 'housenumber'))
            ->setFlatNumber(Arr::get($data, 'flatnumber'))
            ->setEmail(Arr::get($data, 'email'))
            ->setPhone(Arr::get($data, 'phone'))
            ->setCountry(Arr::get($data, 'country'))
            ->setProvince(Arr::get($data, 'province'))
            ->setType(Arr::get($data, 'type'))
            ->setDefault(Arr::get($data, 'default'))
            ->setIsCoverAddress(Arr::get($data, 'is_cover_address'));

        return $address;
    }
}
