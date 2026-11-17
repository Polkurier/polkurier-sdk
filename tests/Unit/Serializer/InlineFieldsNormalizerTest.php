<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\InlineFieldsNormalizer;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Which keys a record claims when a payload written flat is read back.
 *
 * A record that inlines another writes that one's keys beside its own, so reading them back means
 * knowing, for each key, which level wrote it. The names are read here rather than through a round
 * trip because a record that inlines its own type writes the same key twice and the outer one
 * wins, which no payload can tell apart.
 */
#[CoversClass(InlineFieldsNormalizer::class)]
final class InlineFieldsNormalizerTest extends TestCase
{
    /**
     * The field holding an inlined record contributes that record's keys, never its own: its own
     * key is the one the payload does not carry.
     *
     * @return void
     */
    public function testARecordThatInlinesItsOwnTypeDoesNotClaimTheKeyItNeverWrites(): void
    {
        self::assertSame(['own'], ClaimedNames::of(SelfInliningRecord::class));
    }

    /**
     * Two records that inline each other are walked once, and which one is asked first does not
     * change what either claims.
     *
     * @return void
     */
    public function testTwoRecordsThatInlineEachOtherClaimTheSameNamesWhicheverIsAskedFirst(): void
    {
        $second = ClaimedNames::of(MutuallyInlinedSecond::class);
        $first = ClaimedNames::of(MutuallyInlinedFirst::class);

        self::assertEqualsCanonicalizing(['first_own', 'second_own'], $first);
        self::assertEqualsCanonicalizing(['first_own', 'second_own'], $second);
    }
}

/**
 * Reads the normalizer's own answer, which no payload can show.
 */
final class ClaimedNames extends InlineFieldsNormalizer
{
    /**
     * @param class-string $class
     *
     * @return string[]
     */
    public static function of(string $class): array
    {
        return \array_values(static::writtenNamesOf($class));
    }
}

/**
 * A record that inlines a record of its own type.
 */
class SelfInliningRecord implements MessageInterface
{
    /**
     * @param string|null $own
     * @param SelfInliningRecord|null $nested
     */
    public function __construct(
        /**
         * @var string|null
         */
        #[Serializer\SerializedName('own')]
        public ?string $own = null,

        /**
         * @var SelfInliningRecord|null
         */
        #[InlineFields]
        #[Serializer\SerializedName('nested')]
        public ?SelfInliningRecord $nested = null,
    ) {}
}

/**
 * One half of a pair of records that inline each other.
 */
class MutuallyInlinedFirst implements MessageInterface
{
    /**
     * @param string|null $firstOwn
     * @param MutuallyInlinedSecond|null $second
     */
    public function __construct(
        /**
         * @var string|null
         */
        #[Serializer\SerializedName('first_own')]
        public ?string $firstOwn = null,

        /**
         * @var MutuallyInlinedSecond|null
         */
        #[InlineFields]
        #[Serializer\SerializedName('second')]
        public ?MutuallyInlinedSecond $second = null,
    ) {}
}

/**
 * The other half.
 */
class MutuallyInlinedSecond implements MessageInterface
{
    /**
     * @param string|null $secondOwn
     * @param MutuallyInlinedFirst|null $first
     */
    public function __construct(
        /**
         * @var string|null
         */
        #[Serializer\SerializedName('second_own')]
        public ?string $secondOwn = null,

        /**
         * @var MutuallyInlinedFirst|null
         */
        #[InlineFields]
        #[Serializer\SerializedName('first')]
        public ?MutuallyInlinedFirst $first = null,
    ) {}
}
