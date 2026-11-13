<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\BankAccountsServiceInterface;
use Polkurier\Webservice\Dto\Request\BankAccount\DeleteBankAccountMessage;
use Polkurier\Webservice\Entity\BankAccount\BankAccount;
use Polkurier\Webservice\Entity\BankAccount\SaveBankAccountMessage;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * @see \Polkurier\Webservice\Api\Service\BankAccountsServiceInterface
 */
class BankAccountsService extends AbstractService implements BankAccountsServiceInterface
{
    /**
     * @return BankAccount[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getBankAccounts(): array
    {
        return $this->requestList(ApiMethodEnum::GetBankAccounts, BankAccount::class);
    }

    /**
     * @param SaveBankAccountMessage $saveMessage
     *
     * @return BankAccount
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveBankAccount(SaveBankAccountMessage $saveMessage): BankAccount
    {
        return $this->requestObject(ApiMethodEnum::SaveBankAccount, BankAccount::class, $saveMessage);
    }

    /**
     * @param DeleteBankAccountMessage $deleteMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function deleteBankAccount(DeleteBankAccountMessage $deleteMessage): bool
    {
        return $this->requestAcknowledgement(ApiMethodEnum::DeleteBankAccount, $deleteMessage);
    }
}
