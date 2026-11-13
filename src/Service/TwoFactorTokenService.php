<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\TwoFactorTokenServiceInterface;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\ChangeTransportMethodMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\RegisterMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\ResendMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\VerifyMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Dto\Response\TwoFactorToken\ValidityMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see \Polkurier\Webservice\Api\Service\TwoFactorTokenServiceInterface
 */
class TwoFactorTokenService extends AbstractService implements TwoFactorTokenServiceInterface
{
    /**
     * @param RegisterMessage $registerMessage
     *
     * @return TwoFactorToken
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function twoFactorTokenRegister(RegisterMessage $registerMessage): TwoFactorToken
    {
        return $this->requestObject(
            ApiMethodEnum::TwoFactorTokenRegister,
            TwoFactorToken::class,
            $registerMessage,
        );
    }

    /**
     * @param ResendMessage $resendMessage
     *
     * @return TwoFactorToken
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function twoFactorTokenResend(ResendMessage $resendMessage): TwoFactorToken
    {
        return $this->requestObject(
            ApiMethodEnum::TwoFactorTokenResend,
            TwoFactorToken::class,
            $resendMessage,
        );
    }

    /**
     * @param VerifyMessage $verifyMessage
     *
     * @return ValidityMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function twoFactorTokenVerify(VerifyMessage $verifyMessage): ValidityMessage
    {
        return $this->requestObject(
            ApiMethodEnum::TwoFactorTokenVerify,
            ValidityMessage::class,
            $verifyMessage,
        );
    }

    /**
     * @param ChangeTransportMethodMessage $changeTransportMethodMessage
     *
     * @return TwoFactorToken
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function twoFactorTokenChangeTransportMethod(
        ChangeTransportMethodMessage $changeTransportMethodMessage
    ): TwoFactorToken {
        return $this->requestObject(
            ApiMethodEnum::TwoFactorTokenChangeTransportMethod,
            TwoFactorToken::class,
            $changeTransportMethodMessage,
        );
    }
}
