<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Builder;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Builder\Entity\Order\PackItemBuilder;
use Polkurier\Webservice\Builder\Request\Courier\GetPointMessageBuilder;
use Polkurier\Webservice\Builder\Request\Courier\PickupMessageBuilder;
use Polkurier\Webservice\Builder\Request\Courier\PickupQueryMessageBuilder;
use Polkurier\Webservice\Dto\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Dto\Request\Courier\PickupMessage;
use Polkurier\Webservice\Entity\Order\PackItem;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Test\Support\SourceClasses;

/**
 * A builder is an alternative spelling of the constructor, not a second set of rules.
 */
#[CoversNothing]
final class BuilderBehaviourTest extends TestCase
{
    public function testItBuildsWhatTheConstructorWouldHave(): void
    {
        $built = PackItemBuilder::create()
            ->setLength(30)
            ->setWidth(20)
            ->setHeight(15)
            ->setWeight(2.5)
            ->setType(PackTypeEnum::Standard)
            ->setAmount(3)
            ->build();

        self::assertEquals(
            new PackItem(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard, amount: 3),
            $built,
        );
    }

    /**
     * Read from the constructor rather than written down, because a builder holding its own copy
     * of a default is the thing this is guarding against: change the message's default, forget the
     * builder, and it keeps sending the old value with nothing to say so.
     */
    public function testUntouchedFieldsKeepTheDefaultsTheMessageDeclares(): void
    {
        $built = PackItemBuilder::create()->setLength(30)->setWidth(20)->setHeight(15)->setWeight(2.5)->build();

        foreach ((new \ReflectionClass(PackItem::class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            if (!$parameter->isDefaultValueAvailable()) {
                continue;
            }

            $getter = (\is_bool($parameter->getDefaultValue()) ? 'is' : 'get') . \ucfirst($parameter->getName());

            self::assertSame(
                $parameter->getDefaultValue(),
                $built->{$getter}(),
                \sprintf('$%s did not come from the message', $parameter->getName()),
            );
        }
    }

    /**
     * The point of a builder: a payload assembled across branches, where named arguments would
     * mean carrying every value to one call site.
     */
    public function testItCanBeAssembledInSteps(): void
    {
        // The shape this exists for: filters that may or may not be present, applied one at a
        // time. With named arguments each of these would have to reach a single call.
        $filters = self::filtersFromSomewhereElse();

        $builder = GetPointMessageBuilder::create()->setCouriers(['INPOST_PACZKOMAT']);

        if ($filters['searchQuery'] !== null) {
            $builder->setSearchQuery($filters['searchQuery']);
        }

        if ($filters['limit'] !== null) {
            $builder->setLimit($filters['limit']);
        }

        if ($filters['page'] !== null) {
            $builder->setPage($filters['page']);
        }

        $message = $builder->build();

        self::assertSame('Poznań', $message->searchQuery);
        self::assertSame(5, $message->limit);
        self::assertNull($message->page, 'a filter that did not apply was never set');
    }

    /**
     * Stands in for whatever an application actually has: a request, a form, a config file. The
     * widened return type is the point — the values are not known here.
     *
     * @return array{searchQuery: string|null, limit: int|null, page: int|null}
     */
    private static function filtersFromSomewhereElse(): array
    {
        return ['searchQuery' => 'Poznań', 'limit' => 5, 'page' => null];
    }

    public function testItNamesEveryRequiredFieldThatWasNeverGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(GetPointMessage::class . ' cannot be built without couriers.');

        GetPointMessageBuilder::create()->setFunctions([PointFunctionEnum::Send])->build();
    }

    /**
     * Setting a field twice is the last value, not an error — a builder is for changing your mind.
     */
    public function testTheLastValueGivenWins(): void
    {
        $message = GetPointMessageBuilder::create()
            ->setCouriers(['INPOST_PACZKOMAT'])
            ->setLimit(5)
            ->setLimit(10)
            ->build();

        self::assertSame(10, $message->limit);
    }

    public function testResetForgetsEveryValue(): void
    {
        $builder = GetPointMessageBuilder::create()->setCouriers(['INPOST_PACZKOMAT'])->setLimit(5);

        $this->expectException(InvalidArgumentException::class);

        $builder->reset()->build();
    }

    /**
     * A builder inherits the setters of the base its message extends.
     */
    public function testAPickupBuilderTakesTheSharedQuerySetters(): void
    {
        $builder = PickupMessageBuilder::create();
        self::assertInstanceOf(PickupQueryMessageBuilder::class, $builder);

        $message = $builder
            ->setCourier('UPS')
            ->setShipFrom('61-001')
            ->setPickupDate(new \DateTimeImmutable('2026-10-01'))
            ->build();

        self::assertInstanceOf(PickupMessage::class, $message);
        self::assertSame('61-001', $message->shipFrom);
    }

    /**
     * A container may hand out a subclass that declares its own parameterless constructor, as
     * generated proxies do. It still has the setters and builds the same message.
     */
    public function testASubclassWithItsOwnConstructorBuildsTheSameMessage(): void
    {
        $builder = new class extends PackItemBuilder {
            public bool $constructed = false;

            public function __construct()
            {
                $this->constructed = true;
                parent::__construct();
            }
        };

        $built = $builder->setLength(30)->setWidth(20)->setHeight(15)->setWeight(2.5)->build();

        self::assertTrue($builder->constructed);
        self::assertEquals(new PackItem(length: 30, width: 20, height: 15, weight: 2.5), $built);
        self::assertInstanceOf($builder::class, $builder::create());
    }

    /**
     * Nothing a container would wrap is final: neither a builder class nor its public methods.
     */
    public function testNoBuilderOrPublicBuilderMethodIsFinal(): void
    {
        $builders = SourceClasses::under('Builder');

        // The exact count, so that a sweep which stops reaching the builders fails here instead
        // of passing on what is left.
        self::assertCount(27, $builders, 'a builder was added or removed; update the count here');

        foreach ($builders as $class) {
            $reflection = new \ReflectionClass($class);

            self::assertFalse($reflection->isFinal(), $class . ' is final');

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                self::assertFalse($method->isFinal(), $class . '::' . $method->getName() . '() is final');
            }
        }
    }
}
