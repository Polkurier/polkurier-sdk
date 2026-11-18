<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\PaymentsServiceInterface;
use Polkurier\Webservice\Dto\Request\Payment\GetPaymentInfoMessage;
use Polkurier\Webservice\Dto\Request\Payment\GetPrepaidHistoryMessage;
use Polkurier\Webservice\Dto\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Dto\Response\Payment\PaymentInfoMessage;
use Polkurier\Webservice\Dto\Response\Payment\PrepaidHistoryMessage;
use Polkurier\Webservice\Dto\Response\Payment\RegisteredPaymentMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see PaymentsServiceInterface
 */
class PaymentsService extends AbstractService implements PaymentsServiceInterface
{
    /**
     * @param RegisterPaymentMessage $registerPaymentMessage
     *
     * @return RegisteredPaymentMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function registerPayment(RegisterPaymentMessage $registerPaymentMessage): RegisteredPaymentMessage
    {
        return $this->requestObject(
            ApiMethodEnum::RegisterPayment,
            RegisteredPaymentMessage::class,
            $registerPaymentMessage,
        );
    }

    /**
     * @param GetPaymentInfoMessage $getPaymentInfoMessage
     *
     * @return PaymentInfoMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getPaymentInfo(GetPaymentInfoMessage $getPaymentInfoMessage): PaymentInfoMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetPaymentInfo,
            PaymentInfoMessage::class,
            $getPaymentInfoMessage,
        );
    }

    /**
     * @param GetPrepaidHistoryMessage|null $getPrepaidHistoryMessage
     *
     * @return PrepaidHistoryMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getPrepaidHistory(
        ?GetPrepaidHistoryMessage $getPrepaidHistoryMessage = null
    ): PrepaidHistoryMessage {
        return $this->requestObject(
            ApiMethodEnum::GetPrepaidHistory,
            PrepaidHistoryMessage::class,
            $getPrepaidHistoryMessage,
        );
    }
}
