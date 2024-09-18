<?php

namespace PolkurierWebServiceApi\Methods;

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
            $country = [
                'id' => Arr::get($row, 'id'),
                'name' => Arr::get($row, 'name'),
                'is_ue' => Arr::get($row, 'is_ue', false),
                'is_default' => Arr::get($row, 'is_default', false),
                'provinces' => []
            ];

            foreach (Arr::get($row, 'provinces', []) as $provinceData) {
                $province = [
                    'id' => Arr::get($provinceData, 'id'),
                    'name' => Arr::get($provinceData, 'name')
                ];
                $country['provinces'][] = $province;
            }

            $this->responseData[] = $country;
        }
        return $this;
    }
}
