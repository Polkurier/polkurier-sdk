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
use Polkurier\Webservice\Dto\Request\Payment\GetPaymentInfoMessage;
use Polkurier\Webservice\Dto\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;

#[Group('integration')]
#[CoversNothing]
final class PaymentsTest extends IntegrationTestCase
{
    public function testThePrepaidHistoryReads(): void
    {
        $this->assertReads(fn() => $this->sdk->payments()->getPrepaidHistory());
    }

    public function testATopUpIsRegistered(): string
    {
        self::requireSandbox();

        $payment = $this->sdk->payments()->registerPayment(
            new RegisterPaymentMessage(mode: PaymentModeEnum::Prepaid, amount: 100.0),
        );

        self::assertNotSame('', $payment->getTransactionId());

        return $payment->getTransactionId();
    }

    #[Depends('testATopUpIsRegistered')]
    public function testThePaymentReadsBack(string $transactionId): void
    {
        $this->assertReads(
            fn() => $this->sdk->payments()->getPaymentInfo(new GetPaymentInfoMessage(transactionId: $transactionId)),
        );
    }
}
