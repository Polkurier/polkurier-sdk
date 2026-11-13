<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Dto\Request\BankAccount\DeleteBankAccountMessage;
use Polkurier\Webservice\Entity\BankAccount\SaveBankAccountMessage;

/**
 * Registering an account needs a two-factor code, so saving sends a number the API refuses.
 */
#[Group('integration')]
#[CoversNothing]
final class BankAccountsTest extends IntegrationTestCase
{
    public function testTheAccountsRead(): void
    {
        $this->assertReads(fn() => $this->sdk->bankAccounts()->getBankAccounts());
    }

    public function testAnInvalidNumberIsRefused(): void
    {
        self::assertApiError(
            fn() => self::client(validatePayloads: false)->bankAccounts()->saveBankAccount(
                new SaveBankAccountMessage(name: 'SDK test account', number: 'PL61 1090 1014 0000 0712 1981 2875'),
            ),
            'PARAMETER_INVALID',
        );
    }

    public function testDeletingAnUnknownAccountSucceeds(): void
    {
        self::assertTrue($this->sdk->bankAccounts()->deleteBankAccount(new DeleteBankAccountMessage(id: 'zzzzzz')));
    }
}
