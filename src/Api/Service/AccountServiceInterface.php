<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

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
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * The account: profile, balances, preferences, credentials and telephone numbers.
 *
 * Most integrations call getAccountInfo() first. Its flags say whether the account can place orders
 * and which operations take the security PIN instead of a two-factor code.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither:
 * ```json
 * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
 *  "response": "Brak wymaganego parametru lub jest pusty: [couriers]"}
 * ```
 * ```json
 * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
 * ```
 * The SDK throws these as exceptions. Each method lists the ones it can throw.
 */
interface AccountServiceInterface
{
    /**
     * Fetches the account's profile, balances and outstanding obligations.
     *
     * Orders are refused while `is_email_verified` is false or `is_delete_requested` is true.
     * With `require_agreement_verification` true, the account has to complete identity verification,
     * see {@see \Polkurier\Webservice\Api\Service\AccountVerificationServiceInterface}. `pin_locks`
     * says which operations take the security PIN instead of a two-factor code.
     *
     * Balances are in PLN. The money box has 3 of them: the total held, the part you can withdraw
     * now, and the part already committed to a payout.
     *
     * `is_new_regulations` is true in one response per version of the terms of service the account
     * hasn't accepted: the call that reports it also marks the notice as shown, and later calls answer
     * false, accepted or not. Act on it when it arrives. `is_regulations_accepted` is false once
     * acceptance is due: the account has never accepted any terms, or the current ones are
     * unaccepted and `regulations_acceptance_period` days (see `get_system_info`) have passed since
     * they took effect.
     *
     * With `external_application_id`, the response also has `external_application_settings`: that
     * application's settings for this account, see
     * {@see \Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage::$externalApplicationSettings}.
     *
     * Endpoint: `get_account_info`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_account_info",
     *     "authorization": {"login": "12345", "token": "…"}
     * }
     * ```
     *
     * Response, abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "id": 12345,
     *         "display_name": "Jan Kowalski",
     *         "email": "jan@example.com",
     *         "default_courier": "",
     *         "is_active": true,
     *         "is_regulations_accepted": false,
     *         "is_new_regulations": false,
     *         "is_prices_list_accepted": false,
     *         "is_couriers_regulations_accepted": false,
     *         "is_electronic_invoice_accepted": false,
     *         "is_email_verified": true,
     *         "is_payer_data_complete": true,
     *         "is_verified": true,
     *         "is_delete_requested": false,
     *         "has_phone_number": false,
     *         "require_account_verification": false,
     *         "require_agreement_verification": false,
     *         "payment_type": "INVOICE",
     *         "money_box_balance": 0,
     *         "money_box_payout_available_balance": 0,
     *         "money_box_to_payout_balance": 0,
     *         "prepaid_balance": 0,
     *         "invoice_balance": 0,
     *         "invoice_payment_account_number": null,
     *         "invoice_limit": null,
     *         "invoice_limit_used": null,
     *         "invoice_rebate": 0,
     *         "bonuses_savings": 0,
     *         "default_sender_point": {"INPOST_PACZKOMAT": null, "DPD_POINT": null},
     *         "pin_locks": {"PIN_SETTINGS": false, "BANK_ACCOUNTS_EDIT": false,
     *                       "COD_MONEY_BOX": false},
     *         "settings": {"user_delivery_pickup_myself": false,
     *                      "cover_address_sender_checked": false}
     *     }
     * }
     * ```
     *
     * @param GetAccountInfoMessage|null $getAccountInfoMessage null asks for no application settings
     *
     * @return AccountInfoMessage the payload, see
     *         {@see \Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getAccountInfo(?GetAccountInfoMessage $getAccountInfoMessage = null): AccountInfoMessage;

    /**
     * Changes the account's order-form defaults.
     *
     * Only the fields you send are changed. A sender address id that names none of the account's
     * senders is ignored. `default_sender_point` takes InPost locker, Pocztex point, DPD point and
     * UPS Access Point codes; the InPost codes share one point, as do the Pocztex and the UPS ones.
     * Other codes are ignored.
     *
     * Endpoint: `save_account_settings`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "save_account_settings",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "default_sender_address_id": "ySf",
     *         "default_courier": "UPS",
     *         "default_sender_point": {"INPOST_PACZKOMAT": "POZ01A"}
     *     }
     * }
     * ```
     *
     * Response: the object getAccountInfo() returns, reflecting the change, without application
     * settings. It reports `is_new_regulations` once, as getAccountInfo() does.
     *
     * Common errors:
     * ```json
     * {"status": "error",
     *  "response": "Nazwa kuriera musi zawierać się w zbiorze [UPS, DHL, DPD, …]"}
     * ```
     * It has neither code field.
     *
     * @param SaveAccountSettingsMessage $saveAccountSettingsMessage
     *
     * @return AccountInfoMessage the payload, see
     *         {@see \Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveAccountSettings(
        SaveAccountSettingsMessage $saveAccountSettingsMessage
    ): AccountInfoMessage;

    /**
     * Sets or clears the carrier preselected on the order form.
     *
     * Changes only the carrier, unlike saveAccountSettings(). The carrier must be one the account
     * may use; an empty code clears the default.
     *
     * Endpoint: `change_default_courier`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "change_default_courier",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"courier": "UPS"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error",
     *  "response": "Nazwa kuriera musi zawierać się w zbiorze [UPS, UPS_ES, DHL, DPD, …]"}
     * ```
     *
     * @param ChangeDefaultCourierMessage $changeDefaultCourierMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function changeDefaultCourier(
        ChangeDefaultCourierMessage $changeDefaultCourierMessage
    ): bool;

    /**
     * Sets, or clears, the default drop-off point for one carrier.
     *
     * The carrier codes accepted are `INPOST_PACZKOMAT`, `POCZTEX_PUNKT`, `PACZKA_W_RUCHU`,
     * `DPD_POINT`, `FEDEX_DTR`, `DHL_POINT` and `UPS_AP_POINT_POINT`. `INPOST_PACZKOMAT_KURIER`,
     * `INPOST_PACZKOMAT_ALLEGRO`, `INPOST_PACZKOMAT_ECONOMY`, `INPOST_INTERNATIONAL`,
     * `POCZTEX_PUNKT_PUNKT` and `UPS_AP_POINT_COURIER` are refused as FORBIDDEN, any other code as
     * PARAMETER_INVALID.
     *
     * The point id must belong to that carrier's network. An empty point clears the default.
     *
     * Endpoint: `change_default_sender_point`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "change_default_sender_point",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"courier": "INPOST_PACZKOMAT", "point": "POZ01A"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [courier]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędna wartość parametru [courier]. Musi zawierać się w zbiorze […]"}
     * ```
     * The carrier does not operate points.
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Nie można ustawić domyślnego punktu dla tego przewoźnika"}
     * ```
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Punkt POZ01A jest nieprawidłowy"}
     * ```
     * The carrier does not know the point.
     *
     * @param ChangeDefaultSenderPointMessage $changeDefaultSenderPointMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function changeDefaultSenderPoint(
        ChangeDefaultSenderPointMessage $changeDefaultSenderPointMessage
    ): bool;

    /**
     * Writes one of the account's three addresses: billing, correspondence or contact.
     *
     * Once the account has been issued an invoice, the billing details are locked and only support
     * can change them. The call then fails with a forbidden error that points to the contact form.
     *
     * The legal form is required on the billing address. Send it only for that one.
     *
     * Instead of filling in the correspondence or contact address, you can mark it as the same as
     * the billing one.
     *
     * Endpoint: `save_payer_address`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "save_payer_address",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "address_type": "PAYER",
     *         "user_type": "COMPANY",
     *         "same_as_payer": false,
     *         "name": "Acme sp. z o.o.",
     *         "street": "Długa",
     *         "building_number": "12",
     *         "post_code": "61-001",
     *         "city": "Poznań",
     *         "country": "PL",
     *         "nip_prefix": "PL",
     *         "nip": "7791234567",
     *         "regon": "630000000"
     *     }
     * }
     * ```
     *
     * Response:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "W celu zmiany danych prosimy o kontakt poprzez formularz kontaktowy na stronie www.polkurier.pl"}
     * ```
     * The invoice lock. An invoiced account gets this whatever it sends.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"nip": ["…"], "post_code": ["…"]}}
     * ```
     *
     * @param SavePayerAddressMessage $savePayerAddressMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function savePayerAddress(SavePayerAddressMessage $savePayerAddressMessage): bool;

    /**
     * Changes the account password.
     *
     * FORBIDDEN: these credentials may not change the password. A wrong current password is a
     * VALIDATION_ERROR on `previous_password`.
     *
     * Endpoint: `change_password`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "change_password",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"previous_password": "…", "password": "…"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Brak uprawnień"}
     * ```
     * These credentials may not change the password.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"previous_password": ["…"]}}
     * ```
     * The current password is wrong.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"password": ["…"]}}
     * ```
     * The new password does not meet the strength rules.
     *
     * @param ChangePasswordMessage $changePasswordMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function changePassword(ChangePasswordMessage $changePasswordMessage): bool;

    /**
     * Sets or changes the account's telephone PIN.
     *
     * The code given when contacting support: 4 digits, no run of 3 consecutive (123, 321) or
     * identical (111) digits. Not the security PIN, which validateUserPin() checks.
     *
     * Endpoint: `change_phone_pin`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "change_phone_pin",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"pin": "1234"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "Kod telefoniczny musi mieć 4 cyfry [pin]",
     *  "validation_errors": {"pin": ["Kod telefoniczny musi mieć 4 cyfry"]}}
     * ```
     *
     * @param ChangePinMessage $changePinMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function changePhonePin(ChangePinMessage $changePinMessage): bool;

    /**
     * Checks a security PIN without changing anything.
     *
     * The security PIN unlocks PIN-locked operations such as editing bank accounts. Not the
     * telephone PIN. `get_system_info` reports its length under `security`.
     *
     * A wrong PIN does not cause an error. The response is a success envelope with `valid` set to
     * false, as it is for an account without a security PIN.
     *
     * Endpoint: `validate_user_pin`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "validate_user_pin",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"pin": "1234"}
     * }
     * ```
     *
     * Response, correct PIN:
     * ```json
     * {"status": "success", "response": {"valid": true}}
     * ```
     *
     * Response, wrong PIN (still a success envelope):
     * ```json
     * {"status": "success", "response": {"valid": false}}
     * ```
     *
     * @param ValidatePinMessage $validatePinMessage
     *
     * @return PinValidityMessage the payload, see
     *         {@see \Polkurier\Webservice\Dto\Response\Account\PinValidityMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function validateUserPin(ValidatePinMessage $validatePinMessage): PinValidityMessage;

    /**
     * Fetches the terms of service or the privacy policy.
     *
     * The only method here that needs no credentials, so you can show the terms during registration,
     * before an account exists. The SDK sends no credentials for this call.
     *
     * The content is an HTML fragment in Polish, ready to embed in a page.
     *
     * Endpoint: `get_regulations`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_regulations",
     *     "data": {"type": "regulations"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "id": "00000000-0000-4000-8000-000000000000",
     *         "title": "Regulamin",
     *         "content": "<p><strong>REGULAMIN SERWISU INTERNETOWEGO POLKURIER.PL</strong>…</p>",
     *         "date": "2026-01-20T15:55:06+01:00"
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 11, "error_code": "PARAMS_NOT_SET",
     *  "response": "Brak parametru lub jest w nieprawidłowym formacie: [type] prawidłowy format [regulations,privacy_policy]."}
     * ```
     *
     * @param GetRegulationsMessage|null $getRegulationsMessage null fetches the terms of service
     *
     * @return RegulationsMessage the payload, see
     *         {@see \Polkurier\Webservice\Dto\Response\Account\RegulationsMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getRegulations(?GetRegulationsMessage $getRegulationsMessage = null): RegulationsMessage;

    /**
     * Records the customer's acceptance of the current terms.
     *
     * The id must match the version in force at the time of the call, so read it from
     * getRegulations() just before.
     *
     * Endpoint: `accept_regulations`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "accept_regulations",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"regulations_id": "00000000-0000-4000-8000-000000000000"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędny ID regulaminu"}
     * ```
     * Returned for a malformed id and for a valid one that is no longer current.
     *
     * @param AcceptRegulationsMessage $acceptRegulationsMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function acceptRegulations(AcceptRegulationsMessage $acceptRegulationsMessage): bool;

    /**
     * Lists the account's telephone numbers.
     *
     * At most one number is the default. Two-factor codes sent by text message go to it.
     *
     * Endpoint: `get_phone_numbers`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_phone_numbers",
     *     "authorization": {"login": "12345", "token": "…"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {"id": "aQ2", "number": "500600700", "name": "Komórka", "default": true}
     *     ]
     * }
     * ```
     *
     * An account with none gets `"response": []`.
     *
     * @return PhoneNumber[] a list of
     *         {@see \Polkurier\Webservice\Entity\PhoneNumber\PhoneNumber}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getPhoneNumbers(): array;

    /**
     * Adds a telephone number, optionally making it the account's default.
     *
     * The SDK checks before sending that the number has at least 9 digits. A malformed number is
     * rejected with an error on the `number` field.
     *
     * Adding a number needs no confirmation, but making it the default does. You need a verified
     * two-factor token registered under CHANGE_DEFAULT_PHONE_NUMBER, because future two-factor codes
     * go to the default number. The default must be a Polish mobile number.
     *
     * A number already on the account is returned as stored, with its name unchanged. If the
     * account has given any marketing consent, the number gets the consents for calls and text
     * messages.
     *
     * Endpoint: `save_phone_number`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "save_phone_number",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "number": "500600700",
     *         "name": "Komórka",
     *         "default": true,
     *         "two_factor_token": "8f3c1e2a-…",
     *         "two_factor_code": "123456"
     *     }
     * }
     * ```
     *
     * Response: the stored number, in the same shape getPhoneNumbers() returns.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "Numer telefonu jest nieprawidłowy [number]",
     *  "validation_errors": {"number": ["Numer telefonu jest nieprawidłowy"]}}
     * ```
     * ```json
     * {"status": "error", "status_code": 506001, "error_code": "TWO_FACTOR_CODE_REQUIRED",
     *  "response": "Wymagany kod weryfikacyjny"}
     * ```
     * `default` without a two-factor code.
     * ```json
     * {"status": "error", "status_code": 506005, "error_code": "TWO_FACTOR_INVALID_CODE",
     *  "response": "Błędny kod weryfikacyjny lub kod utracił ważność"}
     * ```
     * Wrong or expired code, or a token not registered under CHANGE_DEFAULT_PHONE_NUMBER.
     * ```json
     * {"status": "error", "status_code": 506004, "error_code": "TWO_FACTOR_NUMBER_NOT_VALID_FOR_TWO_FACTOR",
     *  "response": "Domyślny numer musi być numerem krajowej sieci komórkowej"}
     * ```
     *
     * @param SavePhoneNumberMessage $savePhoneNumberMessage
     *
     * @return PhoneNumber the payload, see
     *         {@see \Polkurier\Webservice\Entity\PhoneNumber\PhoneNumber}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function savePhoneNumber(SavePhoneNumberMessage $savePhoneNumberMessage): PhoneNumber;

    /**
     * Removes a telephone number from the account.
     *
     * The id picks the number; `number` is required but only checked for format, by the SDK too. An
     * unknown id removes nothing and succeeds.
     *
     * The default number can't be removed.
     *
     * Removing a number withdraws its marketing consents for calls and text messages.
     *
     * Endpoint: `delete_phone_number`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "delete_phone_number",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"id": "aQ2", "number": "500600700"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "Numer telefonu jest nieprawidłowy [number]",
     *  "validation_errors": {"number": ["Numer telefonu jest nieprawidłowy"]}}
     * ```
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Nie można usunąć domyślnego numeru telefonu. Najpierw ustaw inny numer jako domyślny i spróbuj ponownie."}
     * ```
     * The id names the default number.
     *
     * @param DeletePhoneNumberMessage $deletePhoneNumberMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function deletePhoneNumber(DeletePhoneNumberMessage $deletePhoneNumberMessage): bool;
}
