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
use Polkurier\Webservice\Builder\Courier\GetPointMessageBuilder;
use Polkurier\Webservice\Builder\Order\PackMessageBuilder;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Enum\Order\PackTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Courier\GetPointMessage;
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

        foreach (new \ReflectionClass(PackMessage::class)->getConstructor()?->getParameters() ?? [] as $parameter) {
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

        $values = new \ReflectionClass(AbstractBuilder::class)->getProperty('values')->getValue($builder);

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
}
