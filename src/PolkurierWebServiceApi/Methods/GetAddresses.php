<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Address;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

class GetAddresses extends AbstractMethod
{

    /**
     * @var string|null
     */
    private $type;

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_addresses';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = [];
        if ($this->type) {
            $data['type'] = $this->type;
        }
        return $data;
    }

    /**
     * @param string|null $type
     * @return GetAddresses
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param Response $response
     * @return GetAddresses
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        foreach ($response->get('response') as $row) {
            $this->responseData[] = (new Address())
                ->setId((string)Arr::get($row, 'id'))
                ->setName((string)Arr::get($row, 'name'))
                ->setCompany((string)Arr::get($row, 'company'))
                ->setPerson((string)Arr::get($row, 'person'))
                ->setCity((string)Arr::get($row, 'city'))
                ->setPostcode((string)Arr::get($row, 'postcode'))
                ->setStreet((string)Arr::get($row, 'street'))
                ->setHouseNumber((string)Arr::get($row, 'housenumber'))
                ->setFlatNumber((string)Arr::get($row, 'flatnumber'))
                ->setEmail((string)Arr::get($row, 'email'))
                ->setPhone((string)Arr::get($row, 'phone'))
                ->setCountry((string)Arr::get($row, 'country'))
                ->setProvince((string)Arr::get($row, 'province'))
                ->setType((string)Arr::get($row, 'type'))
                ->setDefault((bool)Arr::get($row, 'default'))
                ->setCoverAddress((bool)Arr::get($row, 'is_cover_address'));
        }
        return $this;
    }

}
