<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Log;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Config;
use Polkurier\Webservice\Dto\Request\AuthorizationMessage;
use Polkurier\Webservice\Dto\Response\ResponseMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Log\LogSanitizer;

#[CoversClass(LogSanitizer::class)]
final class LogSanitizerTest extends TestCase
{
    private const string NOT_AN_ENVELOPE = '<html>Bad gateway</html>';

    /**
     * Every combination of the two flags.
     *
     * @return array<string, array{bool, bool}>
     */
    public static function loggingFlags(): array
    {
        return [
            'neither kind may be logged' => [false, false],
            'secrets may be logged' => [true, false],
            'personal data may be logged' => [false, true],
            'both may be logged' => [true, true],
        ];
    }

    /**
     * The flags govern the fields the redactors name, and a body that is not an envelope has no
     * fields to name, so it reaches the log under every combination of them.
     *
     * @param bool $logSensitiveData
     * @param bool $logPersonalData
     *
     * @return void
     */
    #[DataProvider('loggingFlags')]
    public function testABodyThatIsNotAnApiResponseReachesTheLogAsItArrived(
        bool $logSensitiveData,
        bool $logPersonalData,
    ): void {
        $prepared = self::prepared(
            "\n  " . self::NOT_AN_ENVELOPE . "  \n",
            $logSensitiveData,
            $logPersonalData,
        );

        self::assertSame(self::NOT_AN_ENVELOPE, $prepared);
    }

    /**
     * Each flag releases the kind its own redactor acts on, and both read what to hide off the
     * classes the caller names.
     *
     * @param bool $logSensitiveData
     * @param bool $logPersonalData
     *
     * @return void
     */
    #[DataProvider('loggingFlags')]
    public function testEachKindIsHiddenUntilItsOwnFlagAllowsIt(
        bool $logSensitiveData,
        bool $logPersonalData,
    ): void {
        $body = (string)\json_encode([ResponseMessage::FIELD_RESPONSE => [
            TwoFactorToken::FIELD_ID => 'DT-20534-8f3a',
            TwoFactorToken::FIELD_PHONE_NUMBER => '500600700',
        ]]);

        $prepared = self::prepared(
            $body,
            $logSensitiveData,
            $logPersonalData,
            [ResponseMessage::FIELD_RESPONSE => TwoFactorToken::class],
        );

        self::assertSame([ResponseMessage::FIELD_RESPONSE => [
            TwoFactorToken::FIELD_ID => $logSensitiveData ? 'DT-20534-8f3a' : '***',
            TwoFactorToken::FIELD_PHONE_NUMBER => $logPersonalData ? '500600700' : '***',
        ]], \json_decode($prepared, true));
    }

    /**
     * Such a body can be any size, so it is cut at {@see LogSanitizer::EXCERPT_LENGTH} bytes and
     * the cut is marked. The limit is in bytes, so a cut inside a character walks back to the
     * character before it: a log formatter that encodes the record as JSON drops a record whose
     * text is not valid UTF-8.
     *
     * @return array<string, array{string, string}>
     */
    public static function bodiesAroundTheExcerptLength(): array
    {
        $full = \str_repeat('a', LogSanitizer::EXCERPT_LENGTH);
        // Two bytes of the first euro sign fit in the excerpt and the third does not.
        $split = \str_repeat('a', LogSanitizer::EXCERPT_LENGTH - 2);

        return [
            'as long as the excerpt' => [$full, $full],
            'one character longer' => [$full . 'a', $full . '…'],
            'cut inside a multi-byte character' => [$split . '€€', $split . '…'],
        ];
    }

    /**
     * @param string $body
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('bodiesAroundTheExcerptLength')]
    public function testABodyThatIsNotAnApiResponseIsCutAtTheExcerptLength(string $body, string $expected): void
    {
        self::assertSame($expected, self::prepared($body));
    }

    /**
     * @param string $body
     * @param bool $logSensitiveData
     * @param bool $logPersonalData
     * @param array<string, class-string> $classes
     *
     * @return string
     */
    private static function prepared(
        string $body,
        bool $logSensitiveData = false,
        bool $logPersonalData = false,
        array $classes = [],
    ): string {
        return (new LogSanitizer())->body(
            new Response(502, [], $body),
            new Config(
                authorization: new AuthorizationMessage(token: 'test-token', login: '20534'),
                platform: 'phpunit',
                platformVersion: '1.0.0',
                logSensitiveData: $logSensitiveData,
                logPersonalData: $logPersonalData,
            ),
            ApiMethodEnum::GetAddresses,
            $classes,
        );
    }
}
