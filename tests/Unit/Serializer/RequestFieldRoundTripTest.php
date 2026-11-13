<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\MessageClasses;
use Polkurier\Webservice\Test\Support\Sample;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Every FIELD_* constant a request class declares must come out as a key when a fully populated
 * instance of that class is serialized. A key that never appears means the normalizer can't reach
 * the property, e.g. because its getter is named after something else.
 */
final class RequestFieldRoundTripTest extends TestCase
{
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
        $inlined = self::inlinedKeys($class);
        foreach ($fields as $name => $key) {
            if (\in_array($key, $inlined, true)) {
                continue;
            }
            self::assertIsString($key);
            self::assertArrayHasKey($key, $payload, \sprintf('%s::%s (%s) is not serialized', $class, $name, $key));
        }
    }

    /**
     * Wire names of the maps whose entries are written into the enclosing object instead.
     *
     * @param class-string $class
     *
     * @return list<mixed>
     */
    private static function inlinedKeys(string $class): array
    {
        $keys = [];
        foreach ((new \ReflectionClass($class))->getProperties() as $property) {
            if ($property->getAttributes(InlineFields::class) === []) {
                continue;
            }
            foreach ($property->getAttributes(SerializedName::class) as $attribute) {
                $keys[] = $attribute->getArguments()[0] ?? null;
            }
        }

        return $keys;
    }
}
