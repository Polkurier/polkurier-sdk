<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Country;
use PolkurierWebServiceApi\Entities\Province;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

/**
 * Class GetSupportedCountriesV2
 * @package PolkurierWebServiceApi\Methods
 *
 */
class GetSupportedCountriesV2 extends AbstractMethod
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'get_supported_countries_v2';
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
     * @return $this|AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        foreach ($response->get('response') as $row) {
            $country = new Country();
            $country->setId(Arr::get($row, 'id'))
                ->setName(Arr::get($row, 'name'))
                ->setIsUe(Arr::get($row, 'is_ue'))
                ->setIsDefault(Arr::get($row, 'is_default'));

            $provinces = [];
            foreach (Arr::get($row, 'provinces', []) as $provinceData) {
                $province = new Province();
                $province->setId(Arr::get($provinceData, 'id'))
                    ->setName(Arr::get($provinceData, 'name'));
                $provinces[] = $province;
            }

            $country->setProvinces($provinces);
            $this->responseData[] = $country;
        }

        return $this;
    }
}
