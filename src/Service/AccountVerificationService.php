<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\AccountVerificationServiceInterface;
use Polkurier\Webservice\Dto\Request\Account\RegisterAccountVerificationMessage;
use Polkurier\Webservice\Dto\Response\Account\AccountVerificationMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see AccountVerificationServiceInterface
 */
class AccountVerificationService extends AbstractService implements AccountVerificationServiceInterface
{
    /**
     * @param RegisterAccountVerificationMessage|null $registerAccountVerificationMessage
     *
     * @return AccountVerificationMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function register(
        ?RegisterAccountVerificationMessage $registerAccountVerificationMessage = null
    ): AccountVerificationMessage {
        return $this->requestObject(
            ApiMethodEnum::RegisterAccountVerification,
            AccountVerificationMessage::class,
            $registerAccountVerificationMessage,
        );
    }

    /**
     * @return AccountVerificationMessage|null
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getInfo(): ?AccountVerificationMessage
    {
        return $this->requestNullableObject(
            ApiMethodEnum::GetAccountVerificationInfo,
            AccountVerificationMessage::class,
        );
    }
}
