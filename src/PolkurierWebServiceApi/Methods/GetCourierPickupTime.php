<?php

namespace PolkurierWebServiceApi\Methods;

use PolkurierWebServiceApi\Entities\CourierPickupDate;
use PolkurierWebServiceApi\Response;
use PolkurierWebServiceApi\Util\Arr;
use PolkurierWebServiceApi\Entities\CourierPickupTimeRange;
use PolkurierWebServiceApi\Util\Dates;

class GetCourierPickupTime extends AbstractMethod
{

    /**
     * @var string
     */
    private $courier = '';

    /**
     * @var string
     */
    private $shipFrom = '';

    /**
     * @var string
     */
    private $shipTo = '';

    /**
     * @var string
     */
    private $parcel = '';

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
     * @return GetCourierPickupTime
     */
    public function setCourier($courier)
    {
        $this->courier = (string)$courier;
        return $this;
    }

    /**
     * @param string $shipFrom
     * @return GetCourierPickupTime
     */
    public function setShipFrom($shipFrom)
    {
        $this->shipFrom = (string)$shipFrom;
        return $this;
    }

    /**
     * @param string $shipTo
     * @return GetCourierPickupTime
     */
    public function setShipTo($shipTo)
    {
        $this->shipTo = (string)$shipTo;
        return $this;
    }

    /**
     * @param string $parcel
     * @return GetCourierPickupTime
     */
    public function setParcel($parcel)
    {
        $this->parcel = (string)$parcel;
        return $this;
    }

    /**
     * @return GetCourierPickupTime
     */
    public function setResponseData(Response $response)
    {
        $this->responseData = [];
        $rawData = (array)$response->get('response');
        foreach ($rawData as $row) {
            $date = Dates::dateTimeOrNull(Arr::get($row, 'pickupdate'));
            if ($date !== null) {
                $this->responseData[] = new CourierPickupDate(
                    $date,
                    array_map(static function ($time) {
                        return new CourierPickupTimeRange(
                            (string)Arr::get((array)$time, 'timefrom', ''),
                            (string)Arr::get((array)$time, 'timeto', '')
                        );
                    }, Arr::get($row, 'time', []))
                );

            }
        }
        return $this;
    }

}
