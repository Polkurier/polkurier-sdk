<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Serializer\SentObjectNormalizer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Which members a `#[ResponseOnly]` mark keeps out of the payload, and what a caller's own
 * `ignored_attributes` does alongside it.
 *
 * The mark drops a member, so it reaches every accessor the object normalizer reads a field from:
 * `get`, `is`, `has` and `can`. A caller's list names members too, and applies to every object in
 * the tree; the members this filter adds for one object apply to that object alone.
 */
#[CoversClass(SentObjectNormalizer::class)]
final class ResponseOnlyFieldsTest extends TestCase
{
    /**
     * @return void
     *
     * @throws \JsonException
     */
    public function testACanAccessorMarkedResponseOnlyIsNotSent(): void
    {
        $record = new class {
            public string $person = 'Jan';

            #[SerializedName('can_print')]
            #[ResponseOnly]
            public function canPrint(): bool
            {
                return true;
            }

            #[SerializedName('is_ready')]
            #[ResponseOnly]
            public function isReady(): bool
            {
                return true;
            }
        };

        self::assertSame(['person' => 'Jan'], self::payloadOf($record));
    }

    /**
     * @return void
     *
     * @throws \JsonException
     */
    public function testTheCallersIgnoredAttributesAreApplied(): void
    {
        $record = new class {
            public string $person = 'Jan';

            public string $city = 'Poznań';
        };

        $payload = self::payloadOf(
            $record,
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['person']],
        );

        self::assertSame(['city' => 'Poznań'], $payload);
    }

    /**
     * A nested object is written through the same filter, so it is told what the caller ignores
     * and not what its parent does not send.
     *
     * @return void
     *
     * @throws \JsonException
     */
    public function testACallersIgnoredAttributesReachANestedObjectAndAParentsResponseOnlyOnesDoNot(): void
    {
        $child = new class {
            public string $label = 'child';

            public string $stamp = 'child stamp';
        };
        $parent = new class($child) {
            public string $label = 'parent';

            #[ResponseOnly]
            public string $stamp = 'parent stamp';

            public function __construct(
                public object $child,
            ) {}
        };

        $payload = self::payloadOf($parent);
        $filtered = self::payloadOf($parent, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['label']]);

        self::assertArrayNotHasKey('stamp', $payload);
        self::assertSame(['label' => 'child', 'stamp' => 'child stamp'], $payload['child'] ?? null);
        self::assertArrayNotHasKey('label', $filtered);
        self::assertSame(['stamp' => 'child stamp'], $filtered['child'] ?? null);
    }

    /**
     * @param object $message
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException
     */
    private static function payloadOf(object $message, array $context = []): array
    {
        $payload = \json_decode(
            (new SerializerProvider())->getSerializer()->serialize($message, 'json', $context),
            true,
            flags: \JSON_THROW_ON_ERROR
        );

        self::assertIsArray($payload);

        return $payload;
    }
}
