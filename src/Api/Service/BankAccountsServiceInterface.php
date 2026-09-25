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
 * Register an account here before an order can use it for COD settlement.
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
 * With the default configuration the SDK throws these as exceptions. Each method lists the ones it
 * can throw.
 */
interface BankAccountsServiceInterface
{
    /**
     * Lists the registered bank accounts.
     *
     * Reading is not behind the PIN lock (saving and deleting are).
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
     * This call needs one of two confirmations (only one, never both):
     *
     * - If bank account editing is PIN-locked, send the PIN with the request. `get_account_info`
     *   reports the lock state under `pin_locks.BANK_ACCOUNTS_EDIT`.
     * - Otherwise, send a confirmed two-factor token registered under the CHANGE_BANK_ACCOUNT_NUMBER
     *   namespace. This call uses up the token, so it can't be reused.
     *
     * The number must be a valid account number. The SDK checks its check digits before sending.
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
     * Thrown when the PIN lock is on and the PIN is missing or wrong.
     * ```json
     * {"status": "error", "status_code": 31, "error_code": "TWO_FACTOR_ALREADY_SENT", "response": "…"}
     * ```
     * Two-factor failures come back with the matching two-factor error code. A missing or invalid
     * confirmation is always reported as an error.
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
     * The same PIN lock as for saving applies: while it is on, send the PIN. Deleting does not need
     * two-factor confirmation.
     *
     * Deleting an id that does not exist also succeeds, so the response doesn't tell you whether
     * anything was removed. List the accounts afterwards if you need to know.
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
