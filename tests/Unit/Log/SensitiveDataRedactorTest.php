<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Log;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Request\Account\ChangePasswordMessage;
use Polkurier\Webservice\Dto\Request\Account\ChangePinMessage;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Dto\Request\BankAccount\SaveBankAccountMessage;
use Polkurier\Webservice\Dto\Request\Common\TokenMessage;
use Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Dto\Request\MoneyBox\CreateMoneyBoxPayoutMessage;
use Polkurier\Webservice\Dto\Request\TwoFactorToken\VerifyMessage;
use Polkurier\Webservice\Dto\Response\Account\AccountVerificationMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Entity\BankAccount;
use Polkurier\Webservice\Http\Request\RequestMessage;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Polkurier\Webservice\Log\SensitiveDataRedactor;
use Polkurier\Webservice\Serializer\ClassMemberReader;
use Polkurier\Webservice\Serializer\WireNameReader;
use Polkurier\Webservice\Test\Support\SourceClasses;

#[CoversClass(SensitiveDataRedactor::class)]
final class SensitiveDataRedactorTest extends TestCase
{
    private const string NUMBER = '61109010140000071219812874';

    /**
     * A request body carries the credentials beside the payload, each written by a class of its
     * own, and the walk reads each level beside the class that wrote it.
     *
     * @return void
     */
    public function testHidesTheSecretsOfEachBlockOfARequestAndKeepsTheRest(): void
    {
        $body = (string)\json_encode([
            RequestMessage::FIELD_AUTHORIZATION => [
                AuthorizationMessage::FIELD_LOGIN => '12345',
                TokenMessage::FIELD_TOKEN => 'secret-token',
            ],
            RequestMessage::FIELD_DATA => [
                BankAccount::FIELD_NUMBER => self::NUMBER,
                SaveBankAccountMessage::FIELD_ACCESS_PIN => '1234',
                SaveBankAccountMessage::FIELD_TWO_FACTOR_CODE => '987654',
            ],
        ]);

        $redacted = \json_decode(
            (new SensitiveDataRedactor())->redactJson($body, [
                RequestMessage::FIELD_AUTHORIZATION => AuthorizationMessage::class,
                RequestMessage::FIELD_DATA => SaveBankAccountMessage::class,
            ]),
            true,
        );

        self::assertSame([
            RequestMessage::FIELD_AUTHORIZATION => [
                AuthorizationMessage::FIELD_LOGIN => '12345',
                TokenMessage::FIELD_TOKEN => '***',
            ],
            RequestMessage::FIELD_DATA => [
                BankAccount::FIELD_NUMBER => self::NUMBER,
                SaveBankAccountMessage::FIELD_ACCESS_PIN => '***',
                SaveBankAccountMessage::FIELD_TWO_FACTOR_CODE => '***',
            ],
        ], $redacted);
    }

    /**
     * The kinds of secret a logged body hides are promised, so an integration can stop hiding them
     * itself. The set only grows, which is why this names each kind, with the class that declares
     * it, rather than the list.
     *
     * @see docs/architecture.md
     *
     * @return array<string, array{class-string, string}>
     */
    public static function kindsTheContractNames(): array
    {
        return [
            'the API token' => [AuthorizationMessage::class, TokenMessage::FIELD_TOKEN],
            'a password' => [ChangePasswordMessage::class, ChangePasswordMessage::FIELD_PASSWORD],
            'the password it replaces' => [
                ChangePasswordMessage::class,
                ChangePasswordMessage::FIELD_PREVIOUS_PASSWORD,
            ],
            'the phone PIN' => [ChangePinMessage::class, ChangePinMessage::FIELD_PIN],
            'the security PIN' => [
                CreateMoneyBoxPayoutMessage::class,
                CreateMoneyBoxPayoutMessage::FIELD_SECURITY_PIN,
            ],
            'the access PIN' => [SaveBankAccountMessage::class, SaveBankAccountMessage::FIELD_ACCESS_PIN],
            'a two-factor code' => [SaveBankAccountMessage::class, SaveBankAccountMessage::FIELD_TWO_FACTOR_CODE],
            'a two-factor token' => [SaveBankAccountMessage::class, SaveBankAccountMessage::FIELD_TWO_FACTOR_TOKEN],
            'the code confirming a token' => [VerifyMessage::class, VerifyMessage::FIELD_CODE],
            'the token a two-factor response registers' => [TwoFactorToken::class, TwoFactorToken::FIELD_ID],
            'an access token' => [
                AccountVerificationMessage::class,
                AccountVerificationMessage::FIELD_ACCESS_TOKEN,
            ],
            'a contact form nonce' => [SaveContactFormMessage::class, SaveContactFormMessage::FIELD_NONCE],
        ];
    }

    #[DataProvider('kindsTheContractNames')]
    public function testHidesEveryKindOfSecretTheContractNames(string $class, string $field): void
    {
        self::assertSame([$field => '***'], self::redacted($class, [$field => 'hunter2']));
    }

    /**
     * Every member the source marks, under the name its class writes it. A mark the reader cannot
     * match to a wire name hides nothing, and nothing else would report it.
     *
     * @return array<string, array{class-string, string}>
     */
    public static function markedFields(): array
    {
        $cases = [];
        foreach (SourceClasses::instantiableUnder() as $name => $class) {
            foreach (ClassMemberReader::readMarkedWith($class, Sensitive::class) as $member) {
                $field = WireNameReader::read($member);
                $cases[$name . ' ' . $field] = [$class, $field];
            }
        }

        self::assertNotEmpty($cases);

        return $cases;
    }

    #[DataProvider('markedFields')]
    public function testHidesEveryFieldMarkedAsASecret(string $class, string $field): void
    {
        self::assertSame([$field => '***'], self::redacted($class, [$field => 'hunter2']));
    }

    /**
     * `id` is the token itself on a two-factor response and an ordinary record id everywhere else,
     * where hiding it would cost the log its reference. The key is the same and the class decides.
     *
     * @return void
     */
    public function testHidesTheIdOfATwoFactorTokenAndKeepsTheIdOfABankAccount(): void
    {
        $row = [BankAccount::FIELD_ID => 'DT-20534-8f3a', BankAccount::FIELD_NAME => 'Firmowe'];

        self::assertSame(
            [TwoFactorToken::FIELD_ID => '***'],
            self::redacted(TwoFactorToken::class, [TwoFactorToken::FIELD_ID => 'DT-20534-8f3a']),
        );
        self::assertSame($row, self::redacted(BankAccount::class, $row));
    }

    /**
     * The mark on the field is the whole rule: no key is hidden for what it is called. A body the
     * walk has no class for therefore goes into the log as it arrived, whatever its keys spell,
     * which is what the same body read as the class that writes it changes.
     *
     * @return void
     */
    public function testHidesWhatAClassMarksAndNothingForItsName(): void
    {
        $body = (string)\json_encode([ChangePinMessage::FIELD_PIN => '1234']);
        $redactor = new SensitiveDataRedactor();

        self::assertSame(
            [ChangePinMessage::FIELD_PIN => '1234'],
            \json_decode($redactor->redactJson($body, []), true),
        );
        self::assertSame(
            [ChangePinMessage::FIELD_PIN => '***'],
            \json_decode($redactor->redactJson($body, [], ChangePinMessage::class), true),
        );
    }

    /**
     * An empty value says the field was there and nothing more, so hiding it would cost the log a
     * fact and give nothing away. A body that is not JSON has no keys to hide anything by.
     *
     * @return void
     */
    public function testLeavesEmptySecretsAndBodiesThatAreNotJsonAlone(): void
    {
        $redactor = new SensitiveDataRedactor();
        $empty = '{"data":{"token":"","pin":null,"access_pin":[]}}';

        self::assertSame(
            $empty,
            $redactor->redactJson($empty, [RequestMessage::FIELD_DATA => AuthorizationMessage::class]),
        );
        self::assertSame('<html>Bad gateway</html>', $redactor->redactJson('<html>Bad gateway</html>', []));
    }

    public function testACopyOfTheMessageHasTheSecretHiddenAndTheOriginalIsUntouched(): void
    {
        $body = '{"authorization":{"login":"1","token":"t0k3n"}}';
        $request = new Request('POST', 'https://api.example.test', [], $body);

        $copy = (new SensitiveDataRedactor())->redactMessage($request, [
            RequestMessage::FIELD_AUTHORIZATION => AuthorizationMessage::class,
        ]);

        self::assertSame('{"authorization":{"login":"1","token":"***"}}', (string)$copy->getBody());
        self::assertSame($body, (string)$request->getBody());
    }

    /**
     * The fields as a logged body carries them: under one key, walked beside the class that wrote
     * them. A class map names the keys of the level below the body's own, and which key that is
     * belongs to the caller, not to the redactor.
     *
     * @param class-string $class
     * @param array<array-key, mixed> $fields the keys as PHP holds them: a wire name that spells a
     *        number is an integer here, as it is after the body is decoded
     *
     * @return array<array-key, mixed>
     */
    private static function redacted(string $class, array $fields): array
    {
        $body = (string)\json_encode([RequestMessage::FIELD_DATA => $fields]);
        $redacted = \json_decode(
            (new SensitiveDataRedactor())->redactJson($body, [RequestMessage::FIELD_DATA => $class]),
            true,
        );

        self::assertIsArray($redacted);
        self::assertIsArray($redacted[RequestMessage::FIELD_DATA]);

        return $redacted[RequestMessage::FIELD_DATA];
    }
}
