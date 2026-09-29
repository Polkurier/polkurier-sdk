<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

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
        $root = \dirname(__DIR__, 3) . '/src/Request';
        $cases = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            $relative = \substr($file->getPathname(), \strlen($root) + 1, -4);
            /** @var class-string $class */
            $class = 'Polkurier\\Webservice\\Request\\' . \str_replace('/', '\\', $relative);

            if (!(new \ReflectionClass($class))->isAbstract()) {
                $cases[$relative] = [$class];
            }
        }

        \ksort($cases);

        return $cases;
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

        $json = new SerializerProvider()->getSerializer()->serialize(self::populate($class), 'json');
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

    /**
     * Builds an instance with every property set to a non-empty value, bypassing the constructor so
     * that its invariants don't get in the way.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private static function populate(string $class): object
    {
        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();

        for ($scope = $reflection; $scope !== false; $scope = $scope->getParentClass()) {
            foreach ($scope->getProperties() as $property) {
                if ($property->isStatic() || $property->getDeclaringClass()->getName() !== $scope->getName()) {
                    continue;
                }

                $value = self::sample($property->getType());
                if (\is_array($value)) {
                    $value = [self::element($scope->getName(), $property->getName())];
                }
                \Closure::bind(
                    static function (object $target) use ($property, $value): void {
                        $target->{$property->getName()} = $value;
                    },
                    null,
                    $scope->getName(),
                )($instance);
            }
        }

        return $instance;
    }

    /**
     * A sample entry for a list property, typed from its docblock.
     *
     * @param class-string $class
     */
    private static function element(string $class, string $property): mixed
    {
        $type = new PhpStanExtractor()->getType($class, $property);
        if ($type instanceof NullableType) {
            $type = $type->getWrappedType();
        }
        if (!$type instanceof CollectionType) {
            return 'x';
        }

        $value = $type->getCollectionValueType();
        if ($value instanceof NullableType) {
            $value = $value->getWrappedType();
        }
        if (!$value instanceof ObjectType) {
            return 'x';
        }

        /** @var class-string $name */
        $name = $value->getClassName();

        return \enum_exists($name) ? $name::cases()[0] : self::populate($name);
    }

    private static function sample(?\ReflectionType $type): mixed
    {
        if ($type instanceof \ReflectionUnionType) {
            $type = $type->getTypes()[0];
        }
        if (!$type instanceof \ReflectionNamedType) {
            return 'x';
        }

        /** @var class-string $name */
        $name = $type->getName();

        return match (true) {
            $name === 'string' => 'x',
            $name === 'int' => 1,
            $name === 'float' => 1.5,
            $name === 'bool' => true,
            $name === 'array', $name === 'iterable' => ['x'],
            $name === \DateTimeInterface::class, $name === \DateTimeImmutable::class => new \DateTimeImmutable('2026-01-01'),
            \enum_exists($name) => $name::cases()[0],
            \class_exists($name) => self::populate($name),
            default => 'x',
        };
    }
}
