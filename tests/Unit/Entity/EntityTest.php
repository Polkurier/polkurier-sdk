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
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;

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
        $root = \dirname(__DIR__, 3) . '/src';
        $classes = [];
        foreach (\glob($root . '/Entity/{*.php,*/*.php}', \GLOB_BRACE) ?: [] as $file) {
            /** @var class-string $class */
            $class = 'Polkurier\\Webservice\\' . \str_replace('/', '\\', \substr($file, \strlen($root) + 1, -4));
            if ((new \ReflectionClass($class))->isAbstract()) {
                continue;
            }
            $classes[\substr($class, \strlen('Polkurier\\Webservice\\Entity\\'))] = [$class];
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
     * Every field the constructor takes can be changed and read back.
     *
     * @param class-string $class
     *
     * @return void
     */
    #[DataProvider('entityClasses')]
    public function testEverySetterChangesWhatItsGetterReturns(string $class): void
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
            self::assertTrue($reflection->hasMethod($setter), $class . '::$' . $name . ' has no setter');
            $getter = \array_values(\array_filter(
                ['get' . \ucfirst($name), 'is' . \ucfirst($name), 'has' . \ucfirst($name)],
                static fn(string $method): bool => $reflection->hasMethod($method),
            ))[0] ?? null;
            self::assertNotNull($getter, $class . '::$' . $name . ' has a setter but no getter');

            $value = $sample->{$getter}();
            self::assertSame($sample, $sample->{$setter}($value), $setter . ' returns the entity');
            self::assertEquals($value, $sample->{$getter}(), $setter);
            ++$checked;
        }
        self::assertGreaterThan(0, $checked);
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
