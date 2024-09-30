<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Address;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class SaveAddress extends AbstractMethod
{

    /**
     * @var array
     */
    private $address = [];

    /**
     * @return string
     */
    public function getName()
    {
        return 'save_address';
    }

    /**
     * @return array[]
     */
    public function getRequestData()
    {
        return $this->address;
    }

    /**
     * @return SaveAddress
     */
    public function setAddress(Address $address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return SaveAddress
     */
    public function setResponseData(Response $response)
    {
        $data = $response->get('response');
        $this->responseData = (new Address())
            ->setId((string)Arr::get($data, 'id', ''))
            ->setName((string)Arr::get($data, 'name', ''))
            ->setCompany((string)Arr::get($data, 'company', ''))
            ->setPerson((string)Arr::get($data, 'person', ''))
            ->setCity((string)Arr::get($data, 'city', ''))
            ->setPostcode((string)Arr::get($data, 'postcode', ''))
            ->setStreet((string)Arr::get($data, 'street', ''))
            ->setHouseNumber((string)Arr::get($data, 'housenumber', ''))
            ->setFlatNumber((string)Arr::get($data, 'flatnumber', ''))
            ->setEmail((string)Arr::get($data, 'email', ''))
            ->setPhone((string)Arr::get($data, 'phone', ''))
            ->setCountry((string)Arr::get($data, 'country', ''))
            ->setProvince((string)Arr::get($data, 'province', ''))
            ->setType((string)Arr::get($data, 'type', ''))
            ->setDefault((bool)Arr::get($data, 'default', false))
            ->setCoverAddress((bool)Arr::get($data, 'is_cover_address', false));
        return $this;
    }

}
