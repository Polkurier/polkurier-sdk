<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Courier\CourierMessageTypeEnum;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Response\System\CourierMessage;
use Polkurier\Webservice\Response\System\SystemInfoMessage;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * Responses shaped as the API sends them still read with unknown fields rejected.
 *
 * With `rejectUnknownFields` on, a single key the response class doesn't map fails the whole call,
 * so every key the API sends has to be mapped.
 */
#[CoversNothing]
final class StrictReadingTest extends TestCase
{
    public function testACarrierNoticeOfTheMessageKindReads(): void
    {
        $notices = self::read(CourierMessage::class . '[]', [
            [
                'courier' => 'FEDEX',
                'title' => 'Płatność kartą za pobranie',
                'message' => '<p>Uwaga! Przewoźnik nalicza opłatę…</p>',
                'type' => 'MESSAGE',
                'severity' => 'WARNING',
                'priority' => 5,
            ],
        ]);

        self::assertIsArray($notices);
        self::assertCount(1, $notices);
        [$notice] = $notices;
        self::assertInstanceOf(CourierMessage::class, $notice);
        self::assertSame(CourierMessageTypeEnum::Message, $notice->getType());
        self::assertSame('Płatność kartą za pobranie', $notice->getTitle());
        self::assertSame(5, $notice->getPriority());
        self::assertSame(0, $notice->getHideTimeout());
    }

    public function testACarrierNoticeOfTheAlertKindReads(): void
    {
        $notices = self::read(CourierMessage::class . '[]', [
            [
                'courier' => 'FEDEX',
                'message' => 'Uwaga! U przewoźnika występują chwilowe utrudnienia…',
                'type' => 'ALERT',
                'severity' => 'WARNING',
                'hide_timeout' => 4,
            ],
        ]);

        self::assertIsArray($notices);
        [$notice] = $notices;
        self::assertInstanceOf(CourierMessage::class, $notice);
        self::assertSame(CourierMessageTypeEnum::Alert, $notice->getType());
        self::assertSame(4, $notice->getHideTimeout());
        self::assertSame('', $notice->getTitle());
        self::assertSame(0, $notice->getPriority());
    }

    public function testTheSystemInfoReadsWithItsFeatureFlags(): void
    {
        $info = self::read(SystemInfoMessage::class, [
            'verification_transfer_amount_pln' => 2,
            'regulations_acceptance_period' => 15,
            'coupons' => ['min_length' => 6],
            'tickets' => ['max_file_size' => 10485760, 'max_image_size' => 20971520, 'max_files_count' => 5],
            'security' => [
                'user_pin_min_length' => 4,
                'user_pin_max_length' => 8,
                'two_factor_code_length' => 6,
                'two_factor_resend_throttle' => 60,
            ],
            'features' => ['sample_feature' => true, 'other_feature' => false],
        ]);

        self::assertInstanceOf(SystemInfoMessage::class, $info);
        self::assertSame(['sample_feature' => true, 'other_feature' => false], $info->getFeatures());
        self::assertSame(15, $info->getRegulationsAcceptancePeriod());
        self::assertSame(6, $info->getCoupons()?->getMinLength());
    }

    /**
     * @param string $format
     * @param array<array-key, mixed> $payload
     *
     * @return mixed
     */
    private static function read(string $format, array $payload): mixed
    {
        $body = \json_encode(['status' => 'success', 'response' => $payload], \JSON_THROW_ON_ERROR);

        return (new ResponseFactory(new SerializerProvider(new SerializerConfig(rejectUnknownFields: true))))
            ->create(new Response(200, [], $body), $format)
            ->getParsedResponse()
            ->getResponse();
    }
}
