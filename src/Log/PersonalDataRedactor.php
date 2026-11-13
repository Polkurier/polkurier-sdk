<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use Polkurier\Webservice\Dto\Request\Account\SavePayerAddressMessage;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessage;
use Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxConfigMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketTimelineItemMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Entity\Address\Address;
use Polkurier\Webservice\Entity\BankAccount\BankAccount;
use Polkurier\Webservice\Entity\Order\Cod;
use Polkurier\Webservice\Entity\Order\CoverAddressMessage;
use Polkurier\Webservice\Entity\Order\OrderAddress;
use Polkurier\Webservice\Entity\PhoneNumber\PhoneNumber;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;

/**
 * Hides personal data in request and response bodies before they are logged.
 *
 * That covers people's and companies' names, street addresses, email addresses, phone numbers,
 * tax and company registration numbers, and bank account numbers. Matching is by key, so the same
 * fields are hidden where they describe a public place, such as a pickup point's street.
 *
 * Account and order identifiers, such as the login, are kept, so a log can still be traced back
 * to the call it came from.
 */
class PersonalDataRedactor extends FieldRedactor
{
    /**
     * Wire names of the fields hidden in every method.
     */
    public const array FIELDS = [
        OrderAddress::FIELD_PERSON,
        OrderAddress::FIELD_COMPANY,
        OrderAddress::FIELD_STREET,
        OrderAddress::FIELD_STREET_NAME,
        Address::FIELD_HOUSE_NUMBER,
        Address::FIELD_FLAT_NUMBER,
        CoverAddressMessage::FIELD_FLAT_NUMBER,
        OrderAddress::FIELD_POSTCODE,
        SavePayerAddressMessage::FIELD_POST_CODE,
        OrderAddress::FIELD_CITY,
        OrderAddress::FIELD_EMAIL,
        OrderAddress::FIELD_PHONE,
        TwoFactorToken::FIELD_PHONE_NUMBER,
        SavePayerAddressMessage::FIELD_NIP,
        SavePayerAddressMessage::FIELD_REGON,
        Cod::FIELD_BANK_ACCOUNT,
        MoneyBoxConfigMessage::FIELD_BANK_ACCOUNT_NUMBER,
        AccountInfoMessage::FIELD_INVOICE_PAYMENT_ACCOUNT_NUMBER,
        AccountInfoMessage::FIELD_DISPLAY_NAME,
        TicketTimelineItemMessage::FIELD_AUTHOR_NAME,
        TicketMessage::FIELD_EMPLOYEE_NAME,
    ];

    /**
     * Wire names of the fields hidden only in some methods, where a generic key holds personal
     * data: `number` is a bank account or phone number there, and `name` a person's name.
     */
    public const array METHOD_FIELDS = [
        ApiMethodEnum::GetBankAccounts->value => [BankAccount::FIELD_NUMBER],
        ApiMethodEnum::SaveBankAccount->value => [BankAccount::FIELD_NUMBER],
        ApiMethodEnum::DeleteBankAccount->value => [BankAccount::FIELD_NUMBER],
        ApiMethodEnum::GetPhoneNumbers->value => [PhoneNumber::FIELD_NUMBER],
        ApiMethodEnum::SavePhoneNumber->value => [PhoneNumber::FIELD_NUMBER],
        ApiMethodEnum::DeletePhoneNumber->value => [PhoneNumber::FIELD_NUMBER],
        ApiMethodEnum::CreateTicket->value => [CreateTicketMessage::FIELD_NAME],
    ];

    /**
     * @param string[] $fields keys hidden in every method
     * @param array<string, string[]> $methodFields keys hidden only in the given methods
     */
    public function __construct(array $fields = self::FIELDS, array $methodFields = self::METHOD_FIELDS)
    {
        parent::__construct($fields, $methodFields);
    }
}
