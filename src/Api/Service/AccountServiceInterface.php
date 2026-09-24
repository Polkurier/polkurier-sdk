<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Request\Account\AcceptRegulationsMessage;
use Polkurier\Webservice\Request\Account\ChangeDefaultCourierMessage;
use Polkurier\Webservice\Request\Account\ChangeDefaultSenderPointMessage;
use Polkurier\Webservice\Request\Account\ChangePasswordMessage;
use Polkurier\Webservice\Request\Account\ChangePinMessage;
use Polkurier\Webservice\Request\Account\DeletePhoneNumberMessage;
use Polkurier\Webservice\Request\Account\GetAccountInfoMessage;
use Polkurier\Webservice\Request\Account\GetRegulationsMessage;
use Polkurier\Webservice\Request\Account\SaveAccountSettingsMessage;
use Polkurier\Webservice\Request\Account\SavePayerAddressMessage;
use Polkurier\Webservice\Request\Account\SavePhoneNumberMessage;
use Polkurier\Webservice\Request\Account\ValidatePinMessage;
use Polkurier\Webservice\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Response\Account\PhoneNumberMessage;
use Polkurier\Webservice\Response\Account\PinValidityMessage;
use Polkurier\Webservice\Response\Account\RegulationsMessage;

/**
 * The account itself: profile, balances, preferences, credentials and telephone numbers.
 *
 * getAccountInfo() is the call to make first in most integrations — it reports the acceptance,
 * verification and PIN-lock flags that decide whether the account can ship at all and which of the
 * calls here need extra confirmation.
 *
 * Errors always arrive with HTTP 200 and `status: "error"`, so branch on the envelope and never on
 * the transport status. Both `status_code` and `error_code` are optional, so an error can carry
 * both, either, or neither:
 * ```json
 * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
 *  "response": "Brak wymaganego parametru lub jest pusty: [couriers]"}
 * ```
 * ```json
 * {"status": "error", "response": "Parametr [orderno] ma niepoprawny format"}
 * ```
 * With the default configuration the SDK raises these rather than returning them; see the
 * exception list on each method.
 */
interface AccountServiceInterface
{
    /**
     * The account's profile, balances and outstanding obligations.
     *
     * Worth treating as a precondition check rather than display data. The acceptance flags decide
     * whether orders will be accepted at all; `require_account_verification` and
     * `require_agreement_verification` gate shipping on some accounts; and `pin_locks` says which
     * calls need the security PIN attached.
     *
     * Balances are in PLN. The money box reports three of them because funds pass through states:
     * the total held, the part withdrawable now, and the part already committed to a payout.
     *
     * Endpoint: `get_account_info`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_account_info",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {}
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
     * @param GetAccountInfoMessage|null $getAccountInfoMessage null omits all three extras
     *
     * @return AccountInfoMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Account\AccountInfoMessage}
     */
    public function getAccountInfo(?GetAccountInfoMessage $getAccountInfoMessage = null): AccountInfoMessage;

    /**
     * Changes the account's order-form defaults.
     *
     * A partial update: only the fields present are touched. The whole thing runs in one
     * transaction, so a failure in any part leaves none of it applied.
     *
     * Returns the full account profile rather than an acknowledgement, which saves a follow-up
     * call to read the new state back.
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
     * Response: the same object getAccountInfo() returns, reflecting the change.
     *
     * Common errors:
     * ```json
     * {"status": "error",
     *  "response": "Nazwa kuriera musi zawierać się w zbiorze [UPS, DHL, DPD, …]"}
     * ```
     * Note this one arrives without either code field.
     *
     * @param SaveAccountSettingsMessage $saveAccountSettingsMessage
     *
     * @return AccountInfoMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Account\AccountInfoMessage}
     */
    public function saveAccountSettings(
        SaveAccountSettingsMessage $saveAccountSettingsMessage
    ): AccountInfoMessage;

    /**
     * Sets the carrier preselected on the order form.
     *
     * A narrower alternative to saveAccountSettings() when the carrier is all that changes. The
     * carrier is validated against what this account may actually use, not against the full list.
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
     * Only carriers that operate points accept this: InPost lockers, Pocztex points, Paczka w
     * Ruchu, DPD, FedEx, DHL and UPS Access Points. Sub-variants normalise onto their parent, so
     * setting it for the InPost courier variant sets it for InPost lockers.
     *
     * The point id is validated against that carrier's own network, so a code belonging to another
     * carrier is rejected rather than stored. An empty point clears the default.
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
     * Raised for a carrier that does not operate points.
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Punkt POZ01A jest nieprawidłowy"}
     * ```
     * Raised when the point is unknown to that carrier.
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
     * Two gates fire before any field is examined. The call is refused outright once the account
     * has been issued an invoice — billing details are locked at that point and only support can
     * change them, which is reported as a forbidden error naming the contact form. And the legal
     * form is mandatory on the billing address; on the other two it is ignored.
     *
     * The correspondence and contact addresses may be declared identical to the billing one rather
     * than filled in.
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
     * The invoice lock, checked before anything else — an account that has been invoiced gets this
     * whatever it sends.
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
     * The current password is checked first, and a missing or wrong one is refused as forbidden
     * rather than as a validation failure — so the error does not distinguish "you did not send
     * it" from "it was wrong".
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
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"password": ["…"]}}
     * ```
     * Raised when the new password does not meet the strength rules.
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
     * This is the code quoted when contacting support, and the same code that unlocks PIN-guarded
     * operations such as editing bank accounts. Its permitted length is policy rather than
     * contract — read it from `get_system_info` under `security` instead of hard-coding it.
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
     * A wrong PIN is a verdict, not an error: the response is a success envelope with the flag
     * cleared. Read the flag rather than relying on an exception.
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
     * Response, wrong PIN — still a success envelope:
     * ```json
     * {"status": "success", "response": {"valid": false}}
     * ```
     *
     * @param ValidatePinMessage $validatePinMessage
     *
     * @return PinValidityMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Account\PinValidityMessage}
     */
    public function validateUserPin(ValidatePinMessage $validatePinMessage): PinValidityMessage;

    /**
     * Fetches the terms of service or the privacy policy.
     *
     * The one method here that needs no credentials, so it can be shown during registration before
     * an account exists. The SDK still sends them when configured; the API simply ignores them.
     *
     * The content is a ready-to-render HTML fragment in Polish, meant to be embedded rather than
     * displayed raw.
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
     * @return RegulationsMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Account\RegulationsMessage}
     */
    public function getRegulations(?GetRegulationsMessage $getRegulationsMessage = null): RegulationsMessage;

    /**
     * Records the customer's acceptance of the current terms.
     *
     * The id must match the version in force at the moment of the call, so read it from
     * getRegulations() immediately beforehand. The check exists so a customer cannot accept a
     * version they were shown earlier and which has since been superseded — which means a stale
     * cached id will be rejected rather than silently accepted.
     *
     * Acceptance is recorded against the parent account, so it covers sub-accounts too.
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
     * Raised both for a malformed id and for a valid one that is no longer current.
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
     * Archived numbers are not listed, so everything returned is live. Exactly one is the default,
     * and that is the one two-factor codes and carrier notifications go to.
     *
     * Endpoint: `get_phone_numbers`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_phone_numbers",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
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
     * @return PhoneNumberMessage[] payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Account\PhoneNumberMessage}[]
     */
    public function getPhoneNumbers(): array;

    /**
     * Adds a telephone number, optionally making it the account's default.
     *
     * The number is stripped to digits and validated, so formatting is irrelevant but a malformed
     * number is rejected naming the `number` field.
     *
     * Adding a number needs no confirmation; making it the default does. That requires a verified
     * two-factor token registered under CHANGE_DEFAULT_PHONE_NUMBER, because the default number is
     * where future two-factor codes will be sent. The sole exception is the first number on an
     * account created without one, which becomes default automatically and skips the check.
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
     *
     * @param SavePhoneNumberMessage $savePhoneNumberMessage
     *
     * @return PhoneNumberMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Account\PhoneNumberMessage}
     */
    public function savePhoneNumber(SavePhoneNumberMessage $savePhoneNumberMessage): PhoneNumberMessage;

    /**
     * Removes a telephone number from the account.
     *
     * Both the id and the number are required, and the number is validated on its own before the
     * id is used, so a malformed number fails even when the id is correct.
     *
     * Removal also withdraws any marketing consents recorded against that number, for both calls
     * and text messages — a side effect worth knowing about, since it is not reversible by
     * re-adding the number.
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
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "…"}
     * ```
     * Raised when the number may not be removed — the default number cannot be deleted while it is
     * the only one.
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
