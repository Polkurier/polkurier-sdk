<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\PickupDate;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;
use PolkurierWebServiceApi\Entities\PickupTimeRange;

class GetCourierPickupTime extends AbstractMethod
{
    /**
     * @var string
     */
    private $courier;
    /**
     * @var string
     */
    private $shipFrom;
    /**
     * @var string
     */
    private $shipTo;
    /**
     * @var string
     */
    private $parcel;

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
        return [
            'courier' => $this->courier,
            'shipfrom' => $this->shipFrom,
            'shipto' => $this->shipTo,
            'parcel' => $this->parcel
        ];
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
     * @param string $shipFrom
     * @return $this
     */
    public function setShipFrom($shipFrom)
    {
        $this->shipFrom = $shipFrom;
        return $this;
    }

    /**
     * @param string $shipTo
     * @return $this
     */
    public function setShipTo($shipTo)
    {
        $this->shipTo = $shipTo;
        return $this;
    }

    /**
     * @param string $parcel
     * @return $this
     */
    public function setParcel($parcel)
    {
        $this->parcel = $parcel;
        return $this;
    }

    /**
     * @param Response $response
     * @return $this|GetCourierPickupTime
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        $rawData = $response->get('response');
        foreach ($rawData as $row) {
            $pickupDate = new PickupDate();
            $pickupDate->setPickupdate(Arr::get($row, 'pickupdate'));

            $timeRanges = [];
            foreach (Arr::get($row, 'time', []) as $time) {
                $timeRange = new PickupTimeRange(
                    Arr::get($time, 'timefrom'),
                    Arr::get($time, 'timeto')
                );
                $timeRanges[] = $timeRange;
            }

            $pickupDate->setTime($timeRanges);
            $this->responseData[] = $pickupDate;
        }

        return $this;
    }
}
