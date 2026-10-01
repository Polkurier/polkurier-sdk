<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Builder;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Builder\Courier\GetPickupTimeMessageBuilder;
use Polkurier\Webservice\Builder\Courier\GetPointMessageBuilder;
use Polkurier\Webservice\Builder\Courier\PickupMessageBuilder;
use Polkurier\Webservice\Builder\Order\PackMessageBuilder;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Courier\GetPointMessage;
use Polkurier\Webservice\Request\Courier\PickupMessage;
use Polkurier\Webservice\Request\Order\PackMessage;

/**
 * A builder is an alternative spelling of the constructor, not a second set of rules.
 */
#[CoversNothing]
final class BuilderBehaviourTest extends TestCase
{
    public function testItBuildsWhatTheConstructorWouldHave(): void
    {
        $built = PackMessageBuilder::create()
            ->length(30)
            ->width(20)
            ->height(15)
            ->weight(2.5)
            ->type(PackTypeEnum::Standard)
            ->amount(3)
            ->build();

        self::assertEquals(
            new PackMessage(length: 30, width: 20, height: 15, weight: 2.5, type: PackTypeEnum::Standard, amount: 3),
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
        $built = PackMessageBuilder::create()->length(30)->width(20)->height(15)->weight(2.5)->build();

        foreach ((new \ReflectionClass(PackMessage::class))->getConstructor()?->getParameters() ?? [] as $parameter) {
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
     * A field never set is not passed at all, which is what makes the above true.
     */
    public function testAFieldNeverSetIsNotSentToTheConstructor(): void
    {
        $builder = PackMessageBuilder::create()->length(30)->width(20)->height(15)->weight(2.5);

        $values = (new \ReflectionClass(AbstractBuilder::class))->getProperty('values')->getValue($builder);

        self::assertSame(['length', 'width', 'height', 'weight'], \array_keys((array)$values));
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

        $builder = GetPointMessageBuilder::create()->couriers(['INPOST_PACZKOMAT']);

        if ($filters['searchQuery'] !== null) {
            $builder->searchQuery($filters['searchQuery']);
        }

        if ($filters['limit'] !== null) {
            $builder->limit($filters['limit']);
        }

        if ($filters['page'] !== null) {
            $builder->page($filters['page']);
        }

        $message = $builder->build();

        self::assertSame('Poznań', $message->getSearchQuery());
        self::assertSame(5, $message->getLimit());
        self::assertNull($message->getPage(), 'a filter that did not apply was never set');
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

        GetPointMessageBuilder::create()->functions([PointFunctionEnum::Send])->build();
    }

    /**
     * Setting a field twice is the last value, not an error — a builder is for changing your mind.
     */
    public function testTheLastValueGivenWins(): void
    {
        $message = GetPointMessageBuilder::create()
            ->couriers(['INPOST_PACZKOMAT'])
            ->limit(5)
            ->limit(10)
            ->build();

        self::assertSame(10, $message->getLimit());
    }

    public function testResetForgetsEveryValue(): void
    {
        $builder = GetPointMessageBuilder::create()->couriers(['INPOST_PACZKOMAT'])->limit(5);

        $this->expectException(InvalidArgumentException::class);

        $builder->reset()->build();
    }

    /**
     * A builder inherits the setters of the message its message extends.
     */
    public function testAPickupBuilderTakesThePickupTimeSetters(): void
    {
        $builder = PickupMessageBuilder::create();
        self::assertInstanceOf(GetPickupTimeMessageBuilder::class, $builder);

        $message = $builder->courier('UPS')->shipFrom('61-001')->pickupDate(new \DateTimeImmutable('2026-10-01'))->build();

        self::assertInstanceOf(PickupMessage::class, $message);
        self::assertSame('61-001', $message->getShipFrom());
    }

    /**
     * A container may hand out a subclass that declares its own parameterless constructor, as
     * Magento's generated interceptors do. It still has the setters and builds the same message.
     */
    public function testASubclassWithItsOwnConstructorBuildsTheSameMessage(): void
    {
        $builder = new class extends PackMessageBuilder {
            public bool $constructed = false;

            public function __construct()
            {
                $this->constructed = true;
                parent::__construct();
            }
        };

        $built = $builder->length(30)->width(20)->height(15)->weight(2.5)->build();

        self::assertTrue($builder->constructed);
        self::assertEquals(new PackMessage(length: 30, width: 20, height: 15, weight: 2.5), $built);
        self::assertInstanceOf($builder::class, $builder::create());
    }

    /**
     * Nothing a container would wrap is final: neither a builder class nor its public methods.
     */
    public function testNoBuilderOrPublicBuilderMethodIsFinal(): void
    {
        $root = \dirname(__DIR__, 3) . '/src/Builder';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        $checked = 0;

        foreach ($files as $file) {
            $class = 'Polkurier\\Webservice\\Builder\\'
                . \str_replace('/', '\\', \substr((string)$file, \strlen($root) + 1, -4));
            $reflection = new \ReflectionClass($class);

            self::assertFalse($reflection->isFinal(), $class . ' is final');

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                self::assertFalse($method->isFinal(), $class . '::' . $method->getName() . '() is final');
            }

            ++$checked;
        }

        self::assertGreaterThan(20, $checked);
    }
}
