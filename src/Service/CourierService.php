<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\CourierServiceInterface;
use Polkurier\Webservice\Dto\Request\Courier\AvailableCarriersMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetCountriesMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPickupTimeMessage;
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Dto\Request\Courier\PickupMessage;
use Polkurier\Webservice\Dto\Response\Carrier;
use Polkurier\Webservice\Dto\Response\Country;
use Polkurier\Webservice\Dto\Response\Courier\PickupResponseMessage;
use Polkurier\Webservice\Dto\Response\Courier\PickupTimeMessage;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see \Polkurier\Webservice\Api\Service\CourierServiceInterface
 */
class CourierService extends AbstractService implements CourierServiceInterface
{
    /**
     * @param AvailableCarriersMessage|null $availableCarriersMessage
     *
     * @return Carrier[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function availableCarriers(
        ?AvailableCarriersMessage $availableCarriersMessage = null
    ): array {
        return $this->requestList(
            ApiMethodEnum::AvailableCarriers,
            Carrier::class,
            $availableCarriersMessage,
        );
    }

    /**
     * @param PickupMessage $pickupMessage
     *
     * @return PickupResponseMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function pickupCourier(PickupMessage $pickupMessage): PickupResponseMessage
    {
        // A refused day comes as `[]` from some carriers instead of the false flag.
        return $this->requestNullableObject(
            ApiMethodEnum::PickupCourier,
            PickupResponseMessage::class,
            $pickupMessage,
        ) ?? new PickupResponseMessage();
    }

    /**
     * @param GetPickupTimeMessage $getPickupTimeMessage
     *
     * @return PickupTimeMessage[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getCourierPickupTime(GetPickupTimeMessage $getPickupTimeMessage): array
    {
        return $this->requestList(
            ApiMethodEnum::GetCourierPickupTime,
            PickupTimeMessage::class,
            $getPickupTimeMessage,
        );
    }

    /**
     * @param GetCountriesMessage $getCountriesMessage
     *
     * @return array<string, string>
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getCountries(GetCountriesMessage $getCountriesMessage): array
    {
        return $this->requestStringMap(ApiMethodEnum::GetCountries, $getCountriesMessage);
    }

    /**
     * @return Country[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getSupportedCountries(): array
    {
        return $this->requestList(ApiMethodEnum::GetSupportedCountries, Country::class);
    }

    /**
     * @param GetPointMessage $getPointMessage
     *
     * @return CourierPoint[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function findCourierPoints(GetPointMessage $getPointMessage): array
    {
        return $this->requestList(
            ApiMethodEnum::GetCourierPoint,
            CourierPoint::class,
            $getPointMessage,
        );
    }

    /**
     * @param string $id
     * @param string[] $couriers
     *
     * @return CourierPoint|null
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getCourierPoint(string $id, array $couriers): ?CourierPoint
    {
        // A search by id returns a single point. Every other query returns a list.
        return $this->requestNullableObject(
            ApiMethodEnum::GetCourierPoint,
            CourierPoint::class,
            new GetPointMessage(couriers: $couriers, id: $id),
        );
    }

}
