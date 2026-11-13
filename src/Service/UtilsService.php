<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\UtilsServiceInterface;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Dto\Response\Util\AuthConfirmationMessage;
use Polkurier\Webservice\Dto\Response\Util\HeartbeatMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see \Polkurier\Webservice\Api\Service\UtilsServiceInterface
 */
class UtilsService extends AbstractService implements UtilsServiceInterface
{
    /**
     * @return HeartbeatMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function heartbeat(): HeartbeatMessage
    {
        return $this->requestObject(
            ApiMethodEnum::Heartbeat,
            HeartbeatMessage::class,
            authorization: false,
        );
    }

    /**
     * @param AuthorizationMessage $authorization
     *
     * @return AuthConfirmationMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function testAuthApi(AuthorizationMessage $authorization): AuthConfirmationMessage
    {
        return $this->requestObject(
            ApiMethodEnum::TestAuthApi,
            AuthConfirmationMessage::class,
            authorization: $authorization,
        );
    }
}
