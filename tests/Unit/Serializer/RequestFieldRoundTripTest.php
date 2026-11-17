<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Entity\Order\Address as OrderAddress;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\WireNameReader;
use Polkurier\Webservice\Test\Support\MessageClasses;
use Polkurier\Webservice\Test\Support\Sample;

/**
 * Every FIELD_* constant a request class declares must come out as a key when a fully populated
 * instance of that class is serialized, except the fields only the API sends. A key that never
 * appears means the normalizer can't reach the property, e.g. because its getter is named after
 * something else.
 */
#[CoversNothing]
final class RequestFieldRoundTripTest extends TestCase
{
    /**
     * Constants naming a key no property holds, because the serializer writes it itself, by the
     * class that declares them.
     *
     * @var array<class-string, list<string>>
     */
    private const array SYNTHETIC_FIELDS = [
        // Written by RequestQuirksNormalizer from the flat number, inside an order's cover address.
        OrderAddress::class => ['FIELD_COVER_FLAT_NUMBER'],
    ];

    /**
     * The exceptions name constants, so a constant that is renamed or dropped takes its exception
     * with it instead of quietly excusing a field that is now serialized.
     *
     * @return void
     */
    public function testEverySyntheticFieldStillNamesAConstant(): void
    {
        foreach (self::SYNTHETIC_FIELDS as $owner => $constants) {
            foreach ($constants as $name) {
                self::assertTrue(
                    (new \ReflectionClass($owner))->hasConstant($name),
                    $owner . '::' . $name . ' no longer exists; drop it from ' . self::class,
                );
            }
        }
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function requestClasses(): array
    {
        return \array_map(static fn(string $class): array => [$class], MessageClasses::requests());
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('requestClasses')]
    public function testEveryFieldConstantIsSerialized(string $class): void
    {
        $fields = \array_filter(
            (new \ReflectionClass($class))->getConstants(),
            static fn(string $name): bool => \str_starts_with($name, 'FIELD_'),
            \ARRAY_FILTER_USE_KEY,
        );

        if ($fields === []) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $json = (new SerializerProvider())->getSerializer()->serialize(Sample::of($class), 'json');
        $payload = \json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);
        $skipped = [...self::inlinedKeys($class), ...self::responseOnlyKeys($class)];
        $synthetic = self::syntheticFields($class);
        foreach ($fields as $name => $key) {
            if (\in_array($key, $skipped, true) || \in_array($name, $synthetic, true)) {
                continue;
            }
            self::assertIsString($key);
            self::assertArrayHasKey($key, $payload, \sprintf('%s::%s (%s) is not serialized', $class, $name, $key));
        }
    }

    /**
     * Wire names of the fields the API sends but does not take, which are left out of a request.
     *
     * @param class-string $class
     *
     * @return list<string>
     */
    private static function responseOnlyKeys(string $class): array
    {
        return self::keysMarkedWith($class, ResponseOnly::class);
    }

    /**
     * Wire names of the maps whose entries are written into the enclosing object instead.
     *
     * @param class-string $class
     *
     * @return list<string>
     */
    private static function inlinedKeys(string $class): array
    {
        return self::keysMarkedWith($class, InlineFields::class);
    }

    /**
     * Wire names of the properties carrying the given attribute, read as the serializer reads them.
     *
     * @param class-string $class
     * @param class-string $attribute
     *
     * @return list<string>
     */
    private static function keysMarkedWith(string $class, string $attribute): array
    {
        $keys = [];
        foreach ((new \ReflectionClass($class))->getProperties() as $property) {
            if ($property->getAttributes($attribute) !== []) {
                $keys[] = WireNameReader::read($property);
            }
        }

        return $keys;
    }

    /**
     * Names of the constants of {@see self::SYNTHETIC_FIELDS} that apply to the class.
     *
     * @param class-string $class
     *
     * @return list<string>
     */
    private static function syntheticFields(string $class): array
    {
        $names = [];
        foreach (self::SYNTHETIC_FIELDS as $owner => $constants) {
            if (\is_a($class, $owner, true)) {
                $names = [...$names, ...$constants];
            }
        }

        return $names;
    }
}
