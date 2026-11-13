<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Dto\Request\Account\AcceptRegulationsMessage;
use Polkurier\Webservice\Dto\Request\Account\ChangeDefaultCourierMessage;
use Polkurier\Webservice\Dto\Request\Account\ChangeDefaultSenderPointMessage;
use Polkurier\Webservice\Dto\Request\Account\ChangePasswordMessage;
use Polkurier\Webservice\Dto\Request\Account\ChangePinMessage;
use Polkurier\Webservice\Dto\Request\Account\DeletePhoneNumberMessage;
use Polkurier\Webservice\Dto\Request\Account\SaveAccountSettingsMessage;
use Polkurier\Webservice\Dto\Request\Account\SavePayerAddressMessage;
use Polkurier\Webservice\Dto\Request\Account\ValidatePinMessage;
use Polkurier\Webservice\Entity\PhoneNumber\PhoneNumber;
use Polkurier\Webservice\Entity\PhoneNumber\SavePhoneNumberMessage;
use Polkurier\Webservice\Enum\Account\PayerAddressTypeEnum;

#[Group('integration')]
#[CoversNothing]
final class AccountTest extends IntegrationTestCase
{
    public function testTheProfileReads(): void
    {
        self::assertSame(self::login(), (string)$this->sdk->account()->getAccountInfo()->getId());
    }

    public function testTheCurrentTermsAreReadAndAccepted(): void
    {
        $terms = $this->sdk->account()->getRegulations();
        self::assertNotSame('', $terms->getId());

        self::requireSandbox();
        self::assertTrue($this->sdk->account()->acceptRegulations(
            new AcceptRegulationsMessage(regulationsId: $terms->getId()),
        ));
    }

    public function testAWrongPinIsReported(): void
    {
        self::assertFalse($this->sdk->account()->validateUserPin(new ValidatePinMessage(pin: '0000'))->isValid());
    }

    public function testThePhoneNumbersRead(): void
    {
        $this->assertReads(fn() => $this->sdk->account()->getPhoneNumbers());
    }

    public function testANumberIsAdded(): PhoneNumber
    {
        self::requireSandbox();

        $number = $this->sdk->account()->savePhoneNumber(
            new SavePhoneNumberMessage(number: '500600700', name: 'SDK test'),
        );

        self::assertSame('500600700', $number->getNumber());

        return $number;
    }

    #[Depends('testANumberIsAdded')]
    public function testTheNumberIsRemoved(PhoneNumber $number): void
    {
        self::assertTrue($this->sdk->account()->deletePhoneNumber(
            new DeletePhoneNumberMessage(id: $number->getId(), number: $number->getNumber()),
        ));
    }

    public function testTheDefaultsAreChanged(): void
    {
        self::requireSandbox();
        $account = $this->sdk->account();

        $settings = $account->saveAccountSettings(new SaveAccountSettingsMessage(
            defaultCourier: 'DPD',
            defaultSenderPoint: ['INPOST_PACZKOMAT' => 'POZ01A'],
        ));

        self::assertSame('DPD', $settings->getDefaultCourier());
        self::assertTrue($account->changeDefaultCourier(new ChangeDefaultCourierMessage(courier: 'INPOST_PACZKOMAT')));
        self::assertTrue($account->changeDefaultSenderPoint(
            new ChangeDefaultSenderPointMessage(courier: 'INPOST_PACZKOMAT', point: 'POZ01A'),
        ));
    }

    /**
     * The password can't be put back, so the test sends a change the API refuses.
     */
    public function testAPasswordChangeWithTheWrongCurrentPasswordIsRefused(): void
    {
        self::assertApiError(
            fn() => self::client(validatePayloads: false)->account()->changePassword(
                new ChangePasswordMessage(password: 'abc', previousPassword: 'wrong-password'),
            ),
            'FORBIDDEN',
        );
    }

    public function testATooShortPinIsRefused(): void
    {
        self::assertApiError(
            fn() => self::client(validatePayloads: false)->account()->changePhonePin(new ChangePinMessage(pin: '12')),
            'VALIDATION_ERROR',
        );
    }

    /**
     * The billing address can't be put back, so the test sends one the API refuses.
     */
    public function testABillingAddressWithoutItsLegalFormIsRefused(): void
    {
        self::assertApiError(
            fn() => self::client(validatePayloads: false)->account()->savePayerAddress(new SavePayerAddressMessage(
                addressType: PayerAddressTypeEnum::Payer,
                name: 'Acme sp. z o.o.',
                street: 'Długa',
                buildingNumber: '12',
                postCode: '61-001',
                city: 'Poznań',
                country: 'PL',
                nipPrefix: 'PL',
                nip: '7791234567',
                regon: '630000000',
            )),
            'PARAMETER_INVALID',
        );
    }
}
