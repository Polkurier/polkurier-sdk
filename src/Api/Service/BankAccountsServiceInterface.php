<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\BankAccount\DeleteBankAccountMessage;
use Polkurier\Webservice\Dto\Request\BankAccount\SaveBankAccountMessage;
use Polkurier\Webservice\Entity\BankAccount;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * The account's registered bank accounts.
 *
 * A money box payout to a bank account must name one registered here. An order's cash on delivery
 * takes its account number inline, registered or not.
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
 *
 * @api
 */
interface BankAccountsServiceInterface
{
    /**
     * Lists the registered bank accounts.
     *
     * Reading is not behind the PIN lock. Numbers are digits only.
     *
     * Endpoint: `get_bank_accounts`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_bank_accounts",
     *     "authorization": {"login": "12345", "token": "…"}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "ZQB",
     *             "number": "34102010260000000000000001",
     *             "name": "",
     *             "default": false
     *         }
     *     ]
     * }
     * ```
     *
     * @return BankAccount[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getBankAccounts(): array;

    /**
     * Registers a bank account, or updates the one the message's id names.
     *
     * Registering an account, or changing its number or default flag, needs one of:
     *
     * - If bank account editing is PIN-locked, send the PIN with the request. `get_account_info`
     *   reports the lock state under `pin_locks.BANK_ACCOUNTS_EDIT`.
     * - Otherwise, send a confirmed two-factor token registered under the CHANGE_BANK_ACCOUNT_NUMBER
     *   namespace. This call uses up the token.
     *
     * Renaming needs no two-factor confirmation; the PIN lock still applies.
     *
     * The number must be a Polish account number, and not one of ZUS or a tax office. The SDK checks
     * its check digits before sending.
     *
     * An id that names none of the account's bank accounts gets an empty response, which the SDK
     * throws as MalformedResponseException.
     *
     * Endpoint: `save_bank_account`
     *
     * Request (omit `id` to create, and send whichever confirmation applies):
     * ```json
     * {
     *     "apimethod": "save_bank_account",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "id": "ZQB",
     *         "number": "58 1020 3958 0000 9202 0222 8609",
     *         "name": "Konto firmowe",
     *         "default": true,
     *         "access_pin": "1234",
     *         "two_factor_token": "…",
     *         "two_factor_code": "123456"
     *     }
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"id": "ZQB", "number": "58102039580000920202228609",
     *                  "name": "Konto firmowe", "default": true}
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Numer konta jest niepoprawny [number]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "To rachunek ZUS albo urzędu skarbowego. Podaj numer własnego konta bankowego. [number]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Edycja zablokowana jest kodem PIN. Nie podano kodu PIN lub jest on błędny [access_pin]"}
     * ```
     * Thrown when the PIN lock is on and the PIN is missing or wrong.
     * ```json
     * {"status": "error", "status_code": 506001, "error_code": "TWO_FACTOR_CODE_REQUIRED",
     *  "response": "Wymagany kod weryfikacyjny"}
     * ```
     * ```json
     * {"status": "error", "status_code": 506005, "error_code": "TWO_FACTOR_INVALID_CODE",
     *  "response": "Błędny kod weryfikacyjny lub kod utracił ważność"}
     * ```
     * Wrong or expired code, or a token registered for another operation.
     *
     * @param SaveBankAccountMessage $saveMessage
     *
     * @return BankAccount as stored
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveBankAccount(SaveBankAccountMessage $saveMessage): BankAccount;

    /**
     * Removes a bank account.
     *
     * The same PIN lock as for saving applies: while it is on, send the PIN. Deleting does not need
     * two-factor confirmation.
     *
     * Deleting an id that does not exist also succeeds.
     *
     * Endpoint: `delete_bank_account`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "delete_bank_account",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"id": "ZQB", "access_pin": "1234"}
     * }
     * ```
     *
     * Response, both for a real deletion and for an unknown id:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Edycja zablokowana jest kodem PIN. Nie podano kodu PIN lub jest on błędny [access_pin]"}
     * ```
     *
     * @param DeleteBankAccountMessage $deleteMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function deleteBankAccount(DeleteBankAccountMessage $deleteMessage): bool;
}
