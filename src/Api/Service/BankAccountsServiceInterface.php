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
use Polkurier\Webservice\Request\BankAccount\DeleteBankAccountMessage;
use Polkurier\Webservice\Request\BankAccount\SaveMessage;
use Polkurier\Webservice\Response\BankAccount;

/**
 * Bank accounts that collected cash-on-delivery funds are paid into.
 *
 * An account must be registered here before an order can name it for COD settlement.
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
interface BankAccountsServiceInterface
{
    /**
     * Lists the registered bank accounts.
     *
     * Unlike saving and deleting, reading is not behind the PIN lock.
     *
     * Endpoint: `get_bank_accounts`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_bank_accounts",
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
     * Redirecting where money is paid is treated as sensitive, and the API guards it two ways that are
     * alternatives rather than both:
     *
     * - If the account has PIN-locked bank account editing, the PIN must accompany the request.
     *   `get_account_info` reports the lock state under `pin_locks.BANK_ACCOUNTS_EDIT`.
     * - If it has not, the API demands a confirmed two-factor token instead, registered under the
     *   CHANGE_BANK_ACCOUNT_NUMBER namespace. The token is consumed by this call and cannot be reused.
     *
     * The number is stripped to digits before validation, so formatting and spacing are irrelevant, but
     * it must be a structurally valid account number. Validation runs before the two-factor check, so an
     * invalid number fails without spending the token.
     *
     * Endpoint: `save_bank_account`
     *
     * Request; omit `id` to create, and send whichever of the two guards applies:
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
     *     "response": {"id": "ZQB", "number": "34102010260000000000000001",
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
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN",
     *  "response": "Edycja zablokowana jest kodem PIN. Nie podano kodu PIN lub jest on błędny [access_pin]"}
     * ```
     * Raised when the PIN lock is engaged and the PIN is missing or wrong.
     * ```json
     * {"status": "error", "status_code": 31, "error_code": "TWO_FACTOR_ALREADY_SENT", "response": "…"}
     * ```
     * Two-factor failures surface with the relevant two-factor code; a missing or invalid confirmation
     * is reported rather than silently ignored.
     *
     * @param SaveMessage $saveMessage
     *
     * @return BankAccount as stored
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveBankAccount(SaveMessage $saveMessage): BankAccount;

    /**
     * Removes a bank account.
     *
     * Subject to the same PIN lock as saving, so the PIN must be supplied while that lock is engaged. No
     * two-factor confirmation is required for removal.
     *
     * Deleting an id that does not exist is a silent success rather than an error, so the response says
     * nothing about whether anything was actually removed — list the accounts afterwards if you need to
     * know.
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
