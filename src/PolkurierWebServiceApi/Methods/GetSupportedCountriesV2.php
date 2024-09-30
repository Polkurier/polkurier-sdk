<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\Country;
use PolkurierWebServiceApi\Entities\Province;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

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
     * @return GetSupportedCountriesV2
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        foreach ($response->get('response') as $row) {
            $this->responseData[] = (new Country(
                (string)Arr::get($row, 'id', ''),
                (string)Arr::get($row, 'name', ''),
                (bool)Arr::get($row, 'is_ue', false),
                (bool)Arr::get($row, 'is_default', false),
                array_map(static function ($provinceData) {
                    return new Province(
                        (string)Arr::get($provinceData, 'id', ''),
                        (string)Arr::get($provinceData, 'name', '')
                    );
                }, Arr::get($row, 'provinces', []))
            ));
        }
        return $this;
    }

}
