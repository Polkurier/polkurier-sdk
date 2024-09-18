<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

/**
 * Class GetCourierPoint
 * @package PolkurierWebServiceApi\Methods
 *
 */
class GetCourierPoint extends AbstractMethod
{
    /**
     * @var array
     */
    private $couriers = [];

    /**
     * @var string|null
     */
    private $searchquery = null;

    /**
     * @var array
     */
    private $functions = [];

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_courier_point';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = [
            'couriers' => $this->couriers,
            'functions' => $this->functions
        ];

        if ($this->searchquery) {
            $data['searchquery'] = $this->searchquery;
        }

        return $data;
    }

    /**
     * Ustawia listę przewoźników
     * @param array $couriers
     * @return GetCourierPoint
     */
    public function setCouriers(array $couriers)
    {
        $this->couriers = $couriers;
        return $this;
    }

    /**
     * @param string|null $searchquery
     * @return GetCourierPoint
     */
    public function setSearchquery($searchquery)
    {
        $this->searchquery = $searchquery;
        return $this;
    }

    /**
     * @param array $functions
     * @return GetCourierPoint
     */
    public function setFunctions(array $functions)
    {
        $this->functions = $functions;
        return $this;
    }

    /**
     * @param Response $response
     * @return $this|AbstractMethod
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        foreach ($response->get('response') as $row) {
            $point = [
                'id' => Arr::get($row, 'id'),
                'type' => Arr::get($row, 'type'),
                'provider' => Arr::get($row, 'provider'),
                'city' => Arr::get($row, 'city'),
                'zip' => Arr::get($row, 'zip'),
                'street' => Arr::get($row, 'street'),
                'description' => Arr::get($row, 'description'),
                'latitude' => Arr::get($row, 'latitude'),
                'longitude' => Arr::get($row, 'longitude'),
                'cod' => Arr::get($row, 'cod'),
                'available' => Arr::get($row, 'available'),
                'status' => Arr::get($row, 'status'),
                'send' => Arr::get($row, 'send'),
                'collect' => Arr::get($row, 'collect'),
                'openingHours' => Arr::get($row, 'openingHours'),
                'visible' => Arr::get($row, 'visible'),
                'requireApp' => Arr::get($row, 'requireApp'),
                'requireAppMessage' => Arr::get($row, 'requireAppMessage'),
                'functions' => Arr::get($row, 'functions', [])
            ];

            $this->responseData[] = $point;
        }
        return $this;
    }
}
