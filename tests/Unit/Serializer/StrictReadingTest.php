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
use Polkurier\Webservice\Dto\Response\System\CourierMessage;
use Polkurier\Webservice\Dto\Response\System\SystemInfoMessage;
use Polkurier\Webservice\Enum\Courier\CourierMessageTypeEnum;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerConfig;
use Polkurier\Webservice\Serializer\SerializerProvider;

/**
 * Responses shaped as the API sends them still read with unknown fields rejected.
 *
 * With `strictResponses` on, a single key the response class doesn't map fails the whole call,
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
        self::assertSame(CourierMessageTypeEnum::Message, $notice->type);
        self::assertSame('Płatność kartą za pobranie', $notice->title);
        self::assertSame(5, $notice->priority);
        self::assertSame(0, $notice->hideTimeout);
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
        self::assertSame(CourierMessageTypeEnum::Alert, $notice->type);
        self::assertSame(4, $notice->hideTimeout);
        self::assertSame('', $notice->title);
        self::assertSame(0, $notice->priority);
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
        self::assertSame(['sample_feature' => true, 'other_feature' => false], $info->features);
        self::assertSame(15, $info->regulationsAcceptancePeriod);
        self::assertSame(6, $info->coupons?->minLength);
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

        $serializer = new MessageSerializer(new SerializerProvider(new SerializerConfig(strictResponses: true)));

        return (new ResponseFactory($serializer))
            ->create(new Response(200, [], $body), $format)
            ->getEnvelope()
            ->response;
    }
}
