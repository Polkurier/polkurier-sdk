<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\AccountServiceInterface;
use Polkurier\Webservice\Dto\Request\Account\AcceptRegulationsMessage;
use Polkurier\Webservice\Dto\Request\Account\ChangeDefaultCourierMessage;
use Polkurier\Webservice\Dto\Request\Account\ChangeDefaultSenderPointMessage;
use Polkurier\Webservice\Dto\Request\Account\ChangePasswordMessage;
use Polkurier\Webservice\Dto\Request\Account\ChangePinMessage;
use Polkurier\Webservice\Dto\Request\Account\DeletePhoneNumberMessage;
use Polkurier\Webservice\Dto\Request\Account\GetAccountInfoMessage;
use Polkurier\Webservice\Dto\Request\Account\GetRegulationsMessage;
use Polkurier\Webservice\Dto\Request\Account\SaveAccountSettingsMessage;
use Polkurier\Webservice\Dto\Request\Account\SavePayerAddressMessage;
use Polkurier\Webservice\Dto\Request\Account\ValidatePinMessage;
use Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Dto\Response\Account\PinValidityMessage;
use Polkurier\Webservice\Dto\Response\Account\RegulationsMessage;
use Polkurier\Webservice\Entity\PhoneNumber\PhoneNumber;
use Polkurier\Webservice\Entity\PhoneNumber\SavePhoneNumberMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see AccountServiceInterface
 */
class AccountService extends AbstractService implements AccountServiceInterface
{
    /**
     * @param ?GetAccountInfoMessage $getAccountInfoMessage
     *
     * @return AccountInfoMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getAccountInfo(?GetAccountInfoMessage $getAccountInfoMessage = null): AccountInfoMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetAccountInfo,
            AccountInfoMessage::class,
            $getAccountInfoMessage,
        );
    }

    /**
     * @param SaveAccountSettingsMessage $saveAccountSettingsMessage
     *
     * @return AccountInfoMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveAccountSettings(SaveAccountSettingsMessage $saveAccountSettingsMessage): AccountInfoMessage
    {
        return $this->requestObject(
            ApiMethodEnum::SaveAccountSettings,
            AccountInfoMessage::class,
            $saveAccountSettingsMessage,
        );
    }

    /**
     * @param ChangeDefaultCourierMessage $changeDefaultCourierMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function changeDefaultCourier(ChangeDefaultCourierMessage $changeDefaultCourierMessage): bool
    {
        return $this->requestAcknowledgement(
            ApiMethodEnum::ChangeDefaultCourier,
            $changeDefaultCourierMessage,
        );
    }

    /**
     * @param ChangeDefaultSenderPointMessage $changeDefaultSenderPointMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function changeDefaultSenderPoint(ChangeDefaultSenderPointMessage $changeDefaultSenderPointMessage): bool
    {
        return $this->requestAcknowledgement(
            ApiMethodEnum::ChangeDefaultSenderPoint,
            $changeDefaultSenderPointMessage,
        );
    }

    /**
     * @param SavePayerAddressMessage $savePayerAddressMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function savePayerAddress(SavePayerAddressMessage $savePayerAddressMessage): bool
    {
        return $this->requestAcknowledgement(
            ApiMethodEnum::SavePayerAddress,
            $savePayerAddressMessage,
        );
    }

    /**
     * @param ChangePasswordMessage $changePasswordMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function changePassword(ChangePasswordMessage $changePasswordMessage): bool
    {
        return $this->requestAcknowledgement(ApiMethodEnum::ChangePassword, $changePasswordMessage);
    }

    /**
     * @param ChangePinMessage $changePinMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function changePhonePin(ChangePinMessage $changePinMessage): bool
    {
        return $this->requestAcknowledgement(ApiMethodEnum::ChangePhonePin, $changePinMessage);
    }

    /**
     * @param ValidatePinMessage $validatePinMessage
     *
     * @return PinValidityMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function validateUserPin(ValidatePinMessage $validatePinMessage): PinValidityMessage
    {
        return $this->requestObject(
            ApiMethodEnum::ValidateUserPin,
            PinValidityMessage::class,
            $validatePinMessage,
        );
    }

    /**
     * @param ?GetRegulationsMessage $getRegulationsMessage
     *
     * @return RegulationsMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getRegulations(?GetRegulationsMessage $getRegulationsMessage = null): RegulationsMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetRegulations,
            RegulationsMessage::class,
            $getRegulationsMessage ?? new GetRegulationsMessage(),
            authorization: false,
        );
    }

    /**
     * @param AcceptRegulationsMessage $acceptRegulationsMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function acceptRegulations(AcceptRegulationsMessage $acceptRegulationsMessage): bool
    {
        return $this->requestAcknowledgement(
            ApiMethodEnum::AcceptRegulations,
            $acceptRegulationsMessage,
        );
    }

    /**
     * @return PhoneNumber[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getPhoneNumbers(): array
    {
        return $this->requestList(ApiMethodEnum::GetPhoneNumbers, PhoneNumber::class);
    }

    /**
     * @param SavePhoneNumberMessage $savePhoneNumberMessage
     *
     * @return PhoneNumber
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function savePhoneNumber(SavePhoneNumberMessage $savePhoneNumberMessage): PhoneNumber
    {
        return $this->requestObject(
            ApiMethodEnum::SavePhoneNumber,
            PhoneNumber::class,
            $savePhoneNumberMessage,
        );
    }

    /**
     * @param DeletePhoneNumberMessage $deletePhoneNumberMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function deletePhoneNumber(DeletePhoneNumberMessage $deletePhoneNumberMessage): bool
    {
        return $this->requestAcknowledgement(
            ApiMethodEnum::DeletePhoneNumber,
            $deletePhoneNumberMessage,
        );
    }
}
