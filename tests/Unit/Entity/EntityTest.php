<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Entity;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Polkurier\Webservice\Entity\Order\Cod;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Test\Support\SourceClasses;

/**
 * Entities are the records Polkurier stores. You can keep your own copies, subclass them with
 * columns of your own, and change them through setters.
 */
#[CoversNothing]
final class EntityTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function entityClasses(): array
    {
        $classes = [];
        foreach (SourceClasses::instantiableUnder('Entity') as $name => $class) {
            $classes[\substr($name, \strlen('Entity\\'))] = [$class];
        }
        self::assertNotEmpty($classes);

        return $classes;
    }

    /**
     * @param class-string $class
     *
     * @return void
     */
    #[DataProvider('entityClasses')]
    public function testIsOpenAndMutable(string $class): void
    {
        $reflection = new \ReflectionClass($class);

        self::assertFalse($reflection->isFinal());
        self::assertFalse($reflection->isReadOnly());
    }

    /**
     * Every field the SDK sends can be changed and read back, and a field marked
     * {@see ResponseOnly} can only be read: the serializer drops it from the payload, so a setter
     * for it would change the object and nothing that leaves the SDK.
     *
     * @param class-string $class
     *
     * @return void
     */
    #[DataProvider('entityClasses')]
    public function testEveryFieldCanBeReadAndOnlyASentFieldCanBeChanged(string $class): void
    {
        $sample = Sample::of($class);
        $reflection = new \ReflectionClass($class);
        $checked = 0;
        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();
            $setter = 'set' . \ucfirst($name);
            if ($name === 'rawResponse') {
                continue;
            }
            $getter = \array_values(\array_filter(
                ['get' . \ucfirst($name), 'is' . \ucfirst($name), 'has' . \ucfirst($name)],
                static fn(string $method): bool => $reflection->hasMethod($method),
            ))[0] ?? null;
            self::assertNotNull($getter, $class . '::$' . $name . ' has no getter');

            if ($parameter->getAttributes(ResponseOnly::class) !== []) {
                self::assertFalse(
                    $reflection->hasMethod($setter) && $reflection->getMethod($setter)->isPublic(),
                    $class . '::$' . $name . ' is response-only, so ' . $setter . ' must not be public',
                );
                ++$checked;
                continue;
            }

            self::assertTrue($reflection->hasMethod($setter), $class . '::$' . $name . ' has no setter');
            $value = $sample->{$getter}();
            $other = self::aDifferentValue($value);
            self::assertSame($sample, $sample->{$setter}($other), $setter . ' returns the entity');
            // A value the getter did not already hold, so a setter that assigns nothing fails
            // here instead of returning what it was handed. An object is compared by identity:
            // two instances of the same record carry the same fields, so equality would hold
            // whether the setter stored the one it was handed or kept the one it had.
            if (\is_object($other)) {
                self::assertSame($other, $sample->{$getter}(), $setter);
            } else {
                self::assertEquals($other, $sample->{$getter}(), $setter);
            }
            ++$checked;
        }
        self::assertGreaterThan(0, $checked);
    }

    /**
     * A value of the same type that the sample does not already hold.
     *
     * A record comes back as a second instance of its own class, which the assertion tells apart
     * from the one the sample holds by identity.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private static function aDifferentValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value->modify('+1 day');
        }
        if ($value instanceof \DateTime) {
            return (clone $value)->modify('+1 day');
        }
        if ($value instanceof \BackedEnum) {
            foreach ($value::cases() as $case) {
                if ($case !== $value) {
                    return $case;
                }
            }

            return $value;
        }

        if (\is_object($value)) {
            return Sample::of($value::class);
        }

        return match (true) {
            \is_bool($value) => !$value,
            \is_int($value) => $value + 1,
            \is_float($value) => $value + 1.0,
            \is_string($value) => $value . '-other',
            \is_array($value) => $value === [] ? [] : \array_slice($value, 1),
            default => $value,
        };
    }

    public function testASubclassCanAddAColumnOfItsOwn(): void
    {
        $local = new class(
            person: 'Jan',
            street: 'Długa',
            city: 'Poznań',
            postcode: '61-001',
            email: 'jan@example.com',
            phone: '500600700',
        ) extends Address {
            public ?int $localId = null;
        };
        $local->localId = 7;
        $local->setCity('Gniezno');

        self::assertSame(7, $local->localId);
        self::assertSame('Gniezno', $local->getCity());
    }

    public function testANestedSetterTakesASubclassOfItsOwnType(): void
    {
        $order = Sample::of(Order::class);
        $local = new class(
            person: 'Jan Kowalski',
            street: 'Długa',
            city: 'Poznań',
            postcode: '61-001',
            email: 'jan@example.com',
            phone: '500600700',
        ) extends Address {};

        $order->setSender($local);

        self::assertSame($local, $order->getSender());
    }

    public function testANestedSetterKeepsAnObjectOfItsOwnType(): void
    {
        $order = Sample::of(Order::class);
        $cod = new Cod(amount: 120.0);

        $order->setCod($cod);

        self::assertSame($cod, $order->getCod());
    }

    /**
     * A field only the API sends is left out of the request, so an order read back can be sent again.
     *
     * @return void
     */
    public function testAFieldOnlyResponsesCarryIsNotSent(): void
    {
        $order = Sample::of(Order::class);

        $sent = \json_decode((new SerializerProvider())->getSerializer()->serialize($order, 'json'), true);

        self::assertIsArray($sent);
        foreach ([Order::FIELD_NUMBER, Order::FIELD_STATUS, Order::FIELD_PRICE_NET, Order::FIELD_WAYBILLS] as $field) {
            self::assertArrayNotHasKey($field, $sent, $field);
        }
        self::assertArrayHasKey(Order::FIELD_COURIER, $sent);
    }
}
