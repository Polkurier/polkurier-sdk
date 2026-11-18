<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\GeoServiceInterface;
use Polkurier\Webservice\Dto\Request\Geo\FindCityMessage;
use Polkurier\Webservice\Dto\Response\Geo\CityMessage;
use Polkurier\Webservice\Dto\Response\Geo\TokenMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see \Polkurier\Webservice\Api\Service\GeoServiceInterface
 */
class GeoService extends AbstractService implements GeoServiceInterface
{
    /**
     * @param FindCityMessage $findCityMessage
     *
     * @return CityMessage[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function findCity(FindCityMessage $findCityMessage): array
    {
        return $this->requestList(ApiMethodEnum::FindCity, CityMessage::class, $findCityMessage);
    }

    /**
     * @return TokenMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getMapToken(): TokenMessage
    {
        return $this->requestObject(ApiMethodEnum::GetMapToken, TokenMessage::class);
    }
}
