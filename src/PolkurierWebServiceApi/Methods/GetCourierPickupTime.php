<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;
use PolkurierWebServiceApi\Entities\Pickup;
use PolkurierWebServiceApi\Entities\PickupTimeRange;

class GetCourierPickupTime extends AbstractMethod
{
    private $courier;
    private $shipFrom;
    private $shipTo;
    private $parcel;

    public function getName()
    {
        return 'get_courier_pickup_time';
    }

    public function getRequestData()
    {
        return [
            'courier' => $this->courier,
            'shipfrom' => $this->shipFrom,
            'shipto' => $this->shipTo,
            'parcel' => $this->parcel
        ];
    }

    public function setCourier($courier)
    {
        $this->courier = $courier;
        return $this;
    }

    public function setShipFrom($shipFrom)
    {
        $this->shipFrom = $shipFrom;
        return $this;
    }

    public function setShipTo($shipTo)
    {
        $this->shipTo = $shipTo;
        return $this;
    }

    public function setParcel($parcel)
    {
        $this->parcel = $parcel;
        return $this;
    }

    public function setResponseData(Response $response)
    {
        $this->responseData = [];

        $rawData = $response->get('response');
        foreach ($rawData as $row) {
            $pickup = new Pickup();
            $pickup->setDate(Arr::get($row, 'pickupdate'));

            $timeRanges = [];
            foreach (Arr::get($row, 'time', []) as $time) {
                $timeRange = new PickupTimeRange(
                    Arr::get($time, 'timefrom'),
                    Arr::get($time, 'timeto')
                );
                $timeRanges[] = $timeRange;
            }

            $pickup->setTimeRanges($timeRanges);
            $pickup->setNoCourierOrder(Arr::get($row, 'nocourierorder', false));
            $this->responseData[] = $pickup->toArray();
        }

        return $this;
    }
}
