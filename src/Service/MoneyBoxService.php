<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\MoneyBoxServiceInterface;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Request\MoneyBox\CalculateMoneyBoxPayoutFeeMessage;
use Polkurier\Webservice\Request\MoneyBox\CancelMoneyBoxPayoutMessage;
use Polkurier\Webservice\Request\MoneyBox\CreateMoneyBoxPayoutMessage;
use Polkurier\Webservice\Request\MoneyBox\GetMoneyBoxPayoutsMessage;
use Polkurier\Webservice\Response\MoneyBox\MoneyBoxConfigMessage;
use Polkurier\Webservice\Response\MoneyBox\MoneyBoxFeeMessage;
use Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutListMessage;
use Polkurier\Webservice\Response\MoneyBox\MoneyBoxPayoutMessage;

/**
 * @see MoneyBoxServiceInterface
 */
class MoneyBoxService extends AbstractService implements MoneyBoxServiceInterface
{
    /**
     * @return MoneyBoxConfigMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getConfig(): MoneyBoxConfigMessage
    {
        return $this->requestObject(ApiMethodEnum::GetMoneyBoxConfig, MoneyBoxConfigMessage::class);
    }

    /**
     * @param CalculateMoneyBoxPayoutFeeMessage|null $calculateMoneyBoxPayoutFeeMessage
     *
     * @return MoneyBoxFeeMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function calculatePayoutFee(
        ?CalculateMoneyBoxPayoutFeeMessage $calculateMoneyBoxPayoutFeeMessage = null
    ): MoneyBoxFeeMessage {
        return $this->requestObject(
            ApiMethodEnum::CalculateMoneyBoxPayoutFee,
            MoneyBoxFeeMessage::class,
            $calculateMoneyBoxPayoutFeeMessage,
        );
    }

    /**
     * @param CreateMoneyBoxPayoutMessage $createMoneyBoxPayoutMessage
     *
     * @return MoneyBoxPayoutMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createPayout(CreateMoneyBoxPayoutMessage $createMoneyBoxPayoutMessage): MoneyBoxPayoutMessage
    {
        return $this->requestObject(
            ApiMethodEnum::CreateMoneyBoxPayout,
            MoneyBoxPayoutMessage::class,
            $createMoneyBoxPayoutMessage,
        );
    }

    /**
     * @param CancelMoneyBoxPayoutMessage $cancelMoneyBoxPayoutMessage
     *
     * @return MoneyBoxPayoutMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function cancelPayout(CancelMoneyBoxPayoutMessage $cancelMoneyBoxPayoutMessage): MoneyBoxPayoutMessage
    {
        return $this->requestObject(
            ApiMethodEnum::CancelMoneyBoxPayout,
            MoneyBoxPayoutMessage::class,
            $cancelMoneyBoxPayoutMessage,
        );
    }

    /**
     * @param GetMoneyBoxPayoutsMessage|null $getMoneyBoxPayoutsMessage
     *
     * @return MoneyBoxPayoutListMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getPayouts(?GetMoneyBoxPayoutsMessage $getMoneyBoxPayoutsMessage = null): MoneyBoxPayoutListMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetMoneyBoxPayouts,
            MoneyBoxPayoutListMessage::class,
            $getMoneyBoxPayoutsMessage,
        );
    }
}
