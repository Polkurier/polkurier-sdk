<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Support;

use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Builds instances with every property set to a non-empty sample value, bypassing constructors.
 */
final class Sample
{
    /**
     * A sample of a response class as the API sends it: every property under its wire name.
     *
     * The SDK's serializer writes an object through its getters, under the getters' names, so it can't
     * produce a response. This reads the properties instead.
     *
     * @param class-string $class
     *
     * @return array<array-key, mixed>
     */
    public static function wire(string $class): array
    {
        $metadata = new ClassMetadataFactory(new AttributeLoader());
        $serializer = new Serializer([
            new BackedEnumNormalizer(),
            new DateTimeNormalizer(),
            new PropertyNormalizer($metadata, new MetadataAwareNameConverter($metadata)),
        ]);
        $wire = $serializer->normalize(self::of($class), 'json');
        if (!\is_array($wire)) {
            throw new \LogicException($class . ' is not written as an object.');
        }

        return $wire;
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
    public static function of(string $class): object
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
        $reflection = new \ReflectionProperty($class, $property);
        $doc = (string)$reflection->getDocComment();
        if (!\preg_match('~@var\s+(?:list<|array<[^,>]+,\s*)?(\\\\?[\w\\\\]+)(?:\[\]|>)~', $doc, $match)) {
            return 'x';
        }

        if (\in_array($match[1], ['string', 'int', 'float', 'bool'], true)) {
            return self::value($match[1]);
        }

        $name = self::resolve($reflection->getDeclaringClass(), $match[1]);
        if ($name === null) {
            return 'x';
        }

        return \enum_exists($name) ? $name::cases()[0] : self::of($name);
    }

    /**
     * The class a docblock type names, resolved against the file's imports and namespace.
     *
     * @param \ReflectionClass<object> $scope
     *
     * @return class-string|null
     */
    private static function resolve(\ReflectionClass $scope, string $type): ?string
    {
        if (\str_starts_with($type, '\\')) {
            $candidates = [\substr($type, 1)];
        } else {
            $head = (string)\strtok($type, '\\');
            $candidates = [];
            \preg_match_all(
                '~^use ([\w\\\\]+?)(?: as (\w+))?;$~m',
                (string)\file_get_contents((string)$scope->getFileName()),
                $uses,
                \PREG_SET_ORDER,
            );
            foreach ($uses as $use) {
                $alias = ($use[2] ?? '') !== '' ? $use[2] : \substr($use[1], (int)\strrpos($use[1], '\\') + 1);
                if ($alias === $head) {
                    $candidates[] = $use[1] . \substr($type, \strlen($head));
                }
            }
            $candidates[] = $scope->getNamespaceName() . '\\' . $type;
        }
        foreach ($candidates as $candidate) {
            if (\class_exists($candidate) || \enum_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function sample(?\ReflectionType $type): mixed
    {
        if ($type instanceof \ReflectionUnionType) {
            $type = $type->getTypes()[0];
        }
        if (!$type instanceof \ReflectionNamedType) {
            return 'x';
        }

        return self::value($type->getName());
    }

    /**
     * A non-empty sample value of a type named in PHP: a built-in type, an enum or a class.
     */
    private static function value(string $name): mixed
    {
        /** @var class-string $name */
        return match (true) {
            $name === 'string' => 'x',
            $name === 'int' => 1,
            $name === 'float' => 1.5,
            $name === 'bool' => true,
            $name === 'array', $name === 'iterable' => ['x'],
            $name === \DateTimeInterface::class,
            $name === \DateTimeImmutable::class => new \DateTimeImmutable('2026-01-01'),
            \enum_exists($name) => $name::cases()[0],
            \class_exists($name) => self::of($name),
            default => 'x',
        };
    }
}
