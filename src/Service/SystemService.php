<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\SystemServiceInterface;
use Polkurier\Webservice\Dto\Request\System\GetCouriersMessagesMessage;
use Polkurier\Webservice\Dto\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Dto\Response\System\CouponValidationMessage;
use Polkurier\Webservice\Dto\Response\System\CourierMessage;
use Polkurier\Webservice\Dto\Response\System\SystemInfoMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see SystemServiceInterface
 */
class SystemService extends AbstractService implements SystemServiceInterface
{
    /**
     * @return SystemInfoMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getSystemInfo(): SystemInfoMessage
    {
        return $this->requestObject(ApiMethodEnum::GetSystemInfo, SystemInfoMessage::class);
    }

    /**
     * @param GetCouriersMessagesMessage|null $getCouriersMessagesMessage
     *
     * @return CourierMessage[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getCouriersMessages(?GetCouriersMessagesMessage $getCouriersMessagesMessage = null): array
    {
        return $this->requestList(
            ApiMethodEnum::GetCouriersMessages,
            CourierMessage::class,
            $getCouriersMessagesMessage,
        );
    }

    /**
     * @param ValidateCouponMessage $validateCouponMessage
     *
     * @return CouponValidationMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function validateCouponCode(ValidateCouponMessage $validateCouponMessage): CouponValidationMessage
    {
        return $this->requestObject(
            ApiMethodEnum::ValidateCouponCode,
            CouponValidationMessage::class,
            $validateCouponMessage,
        );
    }
}
