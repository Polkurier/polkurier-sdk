<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Log\PersonalDataRedactor;

#[CoversClass(PersonalDataRedactor::class)]
final class PersonalDataRedactorTest extends TestCase
{
    /**
     * The kinds of personal data a logged body hides are promised, so an integration can stop
     * hiding them itself. The set only grows, which is why this names each kind rather than the
     * list.
     *
     * @see docs/architecture.md
     *
     * @return void
     */
    public function testHidesEveryKindOfPersonalDataTheContractNames(): void
    {
        $body = (string)\json_encode([
            'person' => 'Jan Kowalski',
            'company' => 'Polkurier sp. z o.o.',
            'street' => 'Długa 5/7',
            'streetname' => 'Długa',
            'housenumber' => '5',
            'flatnumber' => '7',
            'flat_number' => '7',
            'postcode' => '61-001',
            'post_code' => '61-001',
            'city' => 'Poznań',
            'email' => 'jan@example.com',
            'phone' => '500600700',
            'phone_number' => '500600700',
            'nip' => '7792308474',
            'regon' => '301686438',
            'codbankaccount' => '61109010140000071219812874',
            'bank_account_number' => '61109010140000071219812874',
            'invoice_payment_account_number' => '61109010140000071219812874',
            'display_name' => 'Jan K.',
            'author_name' => 'Anna N.',
            'employee_name' => 'Piotr W.',
        ]);

        $redacted = \json_decode((new PersonalDataRedactor())->redactJson($body), true);

        // The expectation comes from what went in, so a field the redactor drops instead of
        // hiding fails here too.
        $sent = \json_decode($body, true);
        self::assertSame(\array_fill_keys(\array_keys($sent), '***'), $redacted);
    }

    /**
     * `number` and `name` are personal data only where the method says so, so they are hidden for
     * those methods and kept everywhere else, where they identify an order or a courier.
     *
     * @return void
     */
    public function testHidesGenericKeysOnlyForTheMethodsThatPutPersonalDataInThem(): void
    {
        $body = (string)\json_encode(['number' => '61109010140000071219812874', 'name' => 'Jan Kowalski']);

        $forBankAccounts = (new PersonalDataRedactor())
            ->redactJson($body, ApiMethodEnum::GetBankAccounts);
        $forOrders = (new PersonalDataRedactor())->redactJson($body, ApiMethodEnum::GetOrders);

        self::assertSame(['number' => '***', 'name' => 'Jan Kowalski'], \json_decode($forBankAccounts, true));
        self::assertSame(
            ['number' => '61109010140000071219812874', 'name' => 'Jan Kowalski'],
            \json_decode($forOrders, true),
        );
    }

    /**
     * Every method that puts personal data in a generic key hides it, so dropping one from the map
     * fails here.
     *
     * @return array<string, array{ApiMethodEnum, string}>
     */
    public static function genericKeysPerMethod(): array
    {
        $cases = [];
        foreach (PersonalDataRedactor::METHOD_FIELDS as $method => $fields) {
            foreach ($fields as $field) {
                $cases[$method . ' ' . $field] = [ApiMethodEnum::from($method), $field];
            }
        }

        self::assertNotEmpty($cases);

        return $cases;
    }

    #[DataProvider('genericKeysPerMethod')]
    public function testEachMethodThatHidesAGenericKeyHidesIt(ApiMethodEnum $method, string $field): void
    {
        $body = (string)\json_encode([$field => 'Jan Kowalski']);

        $redacted = (new PersonalDataRedactor())->redactJson($body, $method);

        self::assertSame([$field => '***'], \json_decode($redacted, true));
    }

    /**
     * An account's own identifiers stay, so a log can still be traced back to the call it came
     * from.
     *
     * @return void
     */
    public function testKeepsTheIdentifiersALogIsTracedBy(): void
    {
        $body = (string)\json_encode(['login' => '12345', 'order_number' => '202610-000009', 'person' => 'Jan']);

        $redacted = \json_decode((new PersonalDataRedactor())->redactJson($body), true);

        self::assertSame('12345', $redacted['login']);
        self::assertSame('202610-000009', $redacted['order_number']);
        self::assertSame('***', $redacted['person']);
    }
}
