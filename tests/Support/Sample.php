<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Support;

use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Builds instances with every property set to a non-empty sample value, bypassing constructors.
 */
final class Sample
{
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

        return \enum_exists($name) ? $name::cases()[0] : self::of($name);
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
            \class_exists($name) => self::of($name),
            default => 'x',
        };
    }
}
