<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;

/**
 * Class GetCourierPickupTime
 * @package PolkurierWebServiceApi\Methods
 *
 */
class GetCourierPickupTime extends AbstractMethod
{
    /**
     * Kod przewoźnika
     * @var string
     */
    private $courier;

    /**
     * Kod pocztowy nadawcy
     * @var string|null
     */
    private $shipfrom;

    /**
     * Rodzaj przesyłki
     * @var string
     */
    private $shipmenttype;

    /**
     * @return string
     */
    public function getName()
    {
        return 'get_courier_pickup_time';
    }

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = [
            'courier' => $this->courier,
            'shipmenttype' => $this->shipmenttype,
        ];

        if ($this->shipfrom) {
            $data['shipfrom'] = $this->shipfrom;
        }

        return $data;
    }

    /**
     * @param string $courier
     * @return $this
     */
    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    /**
     * @param string|null $shipfrom
     * @return $this
     */
    public function setShipfrom($shipfrom)
    {
        $this->shipfrom = $shipfrom;
        return $this;
    }

    /**
     * @param string $shipmenttype
     * @return $this
     */
    public function setShipmenttype($shipmenttype)
    {
        $this->shipmenttype = $shipmenttype;
        return $this;
    }

    /**
     * @param Response $response
     * @return $this
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        foreach ($response->get('response') as $row) {
            $pickup = [
                'pickupdate' => Arr::get($row, 'pickupdate'),
                'time' => []
            ];

            foreach (Arr::get($row, 'time', []) as $timeRange) {
                $pickup['time'][] = [
                    'timefrom' => Arr::get($timeRange, 'timefrom'),
                    'timeto' => Arr::get($timeRange, 'timeto')
                ];
            }

            $this->responseData[] = $pickup;
        }
        return $this;
    }
}
