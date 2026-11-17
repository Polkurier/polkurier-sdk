<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Validator;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Dto\Request\System\ValidateCouponMessage;
use Polkurier\Webservice\Enum\Validator\ValidationErrorCodeEnum;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Test\Support\MessageClasses;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;

/**
 * {@see ValidationErrorCodeEnum::Other} tells an integration to show the English instead of
 * translating the code, so a shipped rule reporting it is a fault the SDK knows and refuses to
 * name. Nothing the SDK ships reports it any more.
 *
 * The payloads are built from the request classes by reflection, so a message added later is swept
 * too, and a rule added to it with no code of its own fails here rather than reaching an
 * integration.
 */
#[CoversNothing]
final class FallbackCodeTest extends TestCase
{
    /**
     * @return void
     */
    public function testNoRequestMessageIsRejectedWithTheFallbackCode(): void
    {
        $validator = DefaultValidatorRegistryFactory::create();
        $fallback = [];
        $rejected = [];

        foreach (MessageClasses::requests() as $name => $class) {
            if (!\is_a($class, MessageInterface::class, true)) {
                continue;
            }

            foreach (self::payloads($class) as $variant => $payload) {
                try {
                    $validator->validate($payload);
                    continue;
                } catch (ValidationException $e) {
                    $violations = $e->getViolations();
                }

                $rejected[$class] = true;
                foreach ($violations as $violation) {
                    if ($violation->code === ValidationErrorCodeEnum::Other) {
                        $fallback[] = \sprintf(
                            '%s with %s: %s %s',
                            $name,
                            $variant,
                            $violation->path,
                            $violation->message,
                        );
                    }
                }
            }
        }

        self::assertSame([], $fallback, 'Give each of these faults a code of its own.');
        // The hand-written validators are where a fault with no code of its own ends up, and the
        // coupon one held the last 2. A sweep that stopped reaching them would pass on silence.
        self::assertArrayHasKey(ValidateCouponMessage::class, $rejected);
        self::assertArrayHasKey(RegisterPaymentMessage::class, $rejected);
    }

    /**
     * One payload per way of being wrong: every field empty, every field filled, each field
     * inverted against both, and each field that holds an enum set to every case of it, which is
     * how a rule that applies to one case is reached.
     *
     * @param class-string<MessageInterface> $class
     *
     * @return array<string, MessageInterface>
     */
    private static function payloads(string $class): array
    {
        $filled = Sample::of($class);
        $empty = [];
        foreach (self::properties($class) as $property) {
            $empty[$property->getName()] = true;
        }

        $payloads = [
            'every field empty' => self::build($class, $filled, $empty),
            'every field filled' => self::build($class, $filled, []),
        ];

        foreach (self::properties($class) as $property) {
            $name = $property->getName();
            $payloads['only ' . $name . ' filled'] = self::build($class, $filled, [$name => false] + $empty);
            $payloads['only ' . $name . ' empty'] = self::build($class, $filled, [$name => true]);

            foreach (self::casesOf($property) as $case) {
                $payloads[$name . ' as ' . $case->name] = self::build($class, $filled, $empty, [$name => $case]);
            }
        }

        return $payloads;
    }

    /**
     * Builds an instance property by property, bypassing the constructor so that a combination it
     * refuses can still be put to the validator.
     *
     * @param class-string<MessageInterface> $class
     * @param object $filled an instance of the same class with every property set
     * @param array<string, bool> $empty the properties to empty, by name
     * @param array<string, mixed> $overrides values that are neither the filled nor the empty one
     *
     * @return MessageInterface
     */
    private static function build(string $class, object $filled, array $empty, array $overrides = []): MessageInterface
    {
        $instance = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        foreach (self::properties($class) as $property) {
            $name = $property->getName();
            $value = $property->getValue($filled);

            if (\array_key_exists($name, $overrides)) {
                $value = $overrides[$name];
            } elseif ($empty[$name] ?? false) {
                $value = self::emptied($property->getType(), $value);
            }

            \Closure::bind(
                static function (object $target) use ($name, $value): void {
                    $target->{$name} = $value;
                },
                null,
                $property->getDeclaringClass()->getName(),
            )($instance);
        }

        return $instance;
    }

    /**
     * The empty value of a type, or the filled one for a type that has none.
     *
     * @param \ReflectionType|null $type
     * @param mixed $filled
     *
     * @return mixed
     */
    private static function emptied(?\ReflectionType $type, mixed $filled): mixed
    {
        if ($type === null || $type->allowsNull()) {
            return null;
        }

        $named = $type instanceof \ReflectionUnionType ? $type->getTypes()[0] : $type;
        if (!$named instanceof \ReflectionNamedType) {
            return $filled;
        }

        return match ($named->getName()) {
            'string' => '',
            'int' => 0,
            'float' => 0.0,
            'bool' => false,
            'array', 'iterable' => [],
            // An enum case, a date and an object have no empty form of their own.
            default => $filled,
        };
    }

    /**
     * The cases of the enum a property holds, or none if it holds anything else.
     *
     * @param \ReflectionProperty $property
     *
     * @return list<\UnitEnum>
     */
    private static function casesOf(\ReflectionProperty $property): array
    {
        $type = $property->getType();
        if (!$type instanceof \ReflectionNamedType || !\enum_exists($type->getName())) {
            return [];
        }

        /** @var class-string<\UnitEnum> $enum */
        $enum = $type->getName();

        return $enum::cases();
    }

    /**
     * Every property a class holds, those a parent declares private included.
     *
     * @param class-string $class
     *
     * @return list<\ReflectionProperty>
     */
    private static function properties(string $class): array
    {
        $properties = [];

        for ($scope = new \ReflectionClass($class); $scope !== false; $scope = $scope->getParentClass()) {
            foreach ($scope->getProperties() as $property) {
                if ($property->isStatic() || $property->getDeclaringClass()->getName() !== $scope->getName()) {
                    continue;
                }
                $properties[] = $property;
            }
        }

        return $properties;
    }
}
