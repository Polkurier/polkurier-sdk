<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Test\Support\Sample;
use Polkurier\Webservice\Test\Support\SourceClasses;
use Polkurier\Webservice\Validator\AttributeValidator;
use Polkurier\Webservice\Validator\Rule\ClassRuleInterface;
use Polkurier\Webservice\Validator\Rule\Each;
use Polkurier\Webservice\Validator\Rule\IgnoredWhen;
use Polkurier\Webservice\Validator\Rule\NotBlank;
use Polkurier\Webservice\Validator\Rule\ValueValidatorInterface;
use Polkurier\Webservice\Validator\Violation;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;

/**
 * Every rule a message declares can be made to fail, and every property a class rule names exists.
 *
 * A rule is an attribute, so a field the validator never reads keeps its rules and reports
 * nothing: the message validates, the API rejects it. Reading the code cannot tell the two apart,
 * because the declaration looks the same either way. This builds each field's own class with a
 * value its rules reject and requires a violation naming that field.
 *
 * A class rule names the properties it reads as strings, which the declaration cannot check
 * either: a name the class does not provide reads null, so the rule compares nothing and
 * {@see IgnoredWhen} turns off every rule on the class. Each name is resolved here against the
 * class that writes it.
 *
 * It fixes no rule to a field. What a field must reject is {@see RuleTest}'s and
 * {@see ValidationTest}'s subject; this only requires that whatever it declares is reachable.
 */
#[CoversClass(AttributeValidator::class)]
final class RuleReachabilityTest extends TestCase
{
    /**
     * Values a rule can reject, by the type the field takes. A field whose type admits none of
     * them is left to the two tests above.
     */
    private const array REJECTABLE = [
        'string' => ['', '!!!', 'ZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZZ'],
        'int' => [-1, 999999],
        'float' => [-1.0, 0.0],
        'array' => [[], ['!!!'], [null]],
    ];

    /**
     * @param class-string $class
     * @param string[] $properties the fields of that class that declare rules
     *
     * @return void
     */
    #[DataProvider('classesDeclaringRules')]
    public function testEveryDeclaredRuleCanBeMadeToFail(string $class, array $properties): void
    {
        $reflection = new \ReflectionClass($class);
        $checked = 0;
        foreach ($properties as $name) {
            $property = $reflection->getProperty($name);
            $candidates = self::rejectableValuesFor($property);
            if ($candidates === []) {
                // An enum field cannot hold a value outside its cases, so there is nothing a rule
                // on it could reject that PHP admits in the first place.
                continue;
            }

            self::assertTrue(
                self::canFail($reflection, $name, $candidates),
                $class . '::$' . $name . ' declares rules that no value reaching it can fail',
            );
            ++$checked;
        }

        self::assertGreaterThan(0, $checked, $class . ' was not checked at all');
    }

    /**
     * @param class-string $class
     * @param list<array{string, string}> $named each rule and one property it names
     *
     * @return void
     */
    #[DataProvider('classesDeclaringClassRules')]
    public function testEveryPropertyAClassRuleNamesIsDeclared(string $class, array $named): void
    {
        foreach ($named as [$rule, $property]) {
            self::assertTrue(
                self::declaresProperty($class, $property),
                \sprintf('%s on %s names $%s, which that class does not provide', $rule, $class, $property),
            );
        }
    }

    /**
     * A rule naming a property the message does not provide is refused, instead of passing every
     * payload the class's rules would have rejected.
     *
     * @return void
     */
    public function testAClassRuleNamingAnUnknownPropertyIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$amuont');

        (new AttributeValidator())->validate(new MisspelledBlockMessage());
    }

    /**
     * Every class of the source that declares a rule on a field it takes in its constructor.
     *
     * @return \Generator<string, array{class-string, string[]}>
     */
    public static function classesDeclaringRules(): \Generator
    {
        foreach (SourceClasses::under() as $name => $class) {
            if (!\class_exists($class)) {
                continue;
            }
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || $reflection->isEnum() || $reflection->getConstructor() === null) {
                continue;
            }

            $properties = [];
            foreach ($reflection->getProperties() as $property) {
                if (self::declaresRules($property)) {
                    $properties[] = $property->getName();
                }
            }

            if ($properties !== []) {
                yield $name => [$class, $properties];
            }
        }
    }

    /**
     * Every class of the source that declares a rule on the class, with the properties that rule
     * names. Reflection does not inherit a class attribute, so each declaration is read on the
     * class that writes it.
     *
     * @return \Generator<string, array{class-string, list<array{string, string}>}>
     */
    public static function classesDeclaringClassRules(): \Generator
    {
        foreach (SourceClasses::under() as $name => $class) {
            if (!\class_exists($class)) {
                continue;
            }

            $named = [];
            $rules = (new \ReflectionClass($class))
                ->getAttributes(ClassRuleInterface::class, \ReflectionAttribute::IS_INSTANCEOF);
            foreach ($rules as $rule) {
                foreach ($rule->newInstance()->listProperties() as $property) {
                    $named[] = [$rule->getName(), $property];
                }
            }

            if ($named !== []) {
                yield $name => [$class, $named];
            }
        }
    }

    /**
     * Whether the class, a parent of it, or a record it inlines declares the property.
     *
     * Read through reflection here rather than asked of the SDK, so that a rule and the code
     * resolving it are checked against each other instead of agreeing by construction.
     *
     * @param class-string $class
     * @param string $property
     * @param array<class-string, true> $seen the classes already walked, which records that
     *        inline each other would otherwise send round
     *
     * @return bool
     */
    private static function declaresProperty(string $class, string $property, array $seen = []): bool
    {
        if (isset($seen[$class])) {
            return false;
        }
        $seen[$class] = true;

        // A parent's private property is invisible through the child's reflection, so each class
        // of the chain is asked for its own.
        for ($current = new \ReflectionClass($class); $current !== false; $current = $current->getParentClass()) {
            if ($current->hasProperty($property)) {
                return true;
            }

            foreach ($current->getProperties() as $declared) {
                $type = $declared->getType();
                if ($declared->getAttributes(InlineFields::class) === []
                    || !$type instanceof \ReflectionNamedType
                    || !\class_exists($type->getName())
                ) {
                    continue;
                }
                if (self::declaresProperty($type->getName(), $property, $seen)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param \ReflectionProperty $property
     *
     * @return bool
     */
    private static function declaresRules(\ReflectionProperty $property): bool
    {
        return $property->getAttributes(ValueValidatorInterface::class, \ReflectionAttribute::IS_INSTANCEOF) !== []
            || $property->getAttributes(Each::class, \ReflectionAttribute::IS_INSTANCEOF) !== [];
    }

    /**
     * @param \ReflectionProperty $property
     *
     * @return list<mixed>
     */
    private static function rejectableValuesFor(\ReflectionProperty $property): array
    {
        $type = $property->getType();
        $names = match (true) {
            $type instanceof \ReflectionNamedType => [$type->getName()],
            $type instanceof \ReflectionUnionType, $type instanceof \ReflectionIntersectionType => \array_map(
                static fn(\ReflectionType $each): string => (string)$each,
                $type->getTypes(),
            ),
            default => [],
        };

        $values = [];
        foreach ($names as $name) {
            if (\enum_exists($name)) {
                return [];
            }
            $values = \array_merge($values, self::REJECTABLE[$name] ?? []);
        }
        if ($type?->allowsNull() === true) {
            // A field that may be left out is required by a rule or by nothing, and only the rule
            // can say so.
            $values[] = null;
        }

        return $values;
    }

    /**
     * Whether one of the values, put in that field of an otherwise valid object, is reported.
     *
     * @param \ReflectionClass<object> $class
     * @param string $property
     * @param list<mixed> $candidates
     *
     * @return bool
     */
    private static function canFail(\ReflectionClass $class, string $property, array $candidates): bool
    {
        $validator = new AttributeValidator();
        $names = self::wireNamesOf($class->getName(), $property);
        foreach ($candidates as $candidate) {
            $arguments = self::argumentsFor($class);
            if (!\array_key_exists($property, $arguments)) {
                return false;
            }
            $arguments[$property] = $candidate;

            try {
                $object = $class->newInstanceArgs($arguments);
            } catch (\TypeError) {
                // The field does not take this value at all, which is a stronger check than any
                // rule could be.
                continue;
            }
            if (!$object instanceof MessageInterface) {
                return false;
            }

            foreach (self::violationsOf($validator, $object) as $violation) {
                foreach ($names as $name) {
                    if (\str_contains($violation->path, $name)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * The constructor arguments of a filled sample of the class, by parameter name.
     *
     * @param \ReflectionClass<object> $class
     *
     * @return array<string, mixed>
     */
    private static function argumentsFor(\ReflectionClass $class): array
    {
        $sample = Sample::of($class->getName());
        $arguments = [];
        foreach ($class->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();

            // A parameter a base class holds the property for, passed up by the constructor.
            $owner = $class;
            while ($owner !== false && !$owner->hasProperty($name)) {
                $owner = $owner->getParentClass();
            }

            $arguments[$name] = match (true) {
                $owner !== false && $owner->getProperty($name)->isInitialized($sample)
                    => $owner->getProperty($name)->getValue($sample),
                $parameter->isDefaultValueAvailable() => $parameter->getDefaultValue(),
                default => null,
            };
        }

        return $arguments;
    }

    /**
     * A violation names the field as the payload spells it, so the wire name is what a path
     * carries. The fallbacks cover a field the serializer does not rename.
     *
     * @param class-string $class
     * @param string $property
     *
     * @return list<string>
     */
    private static function wireNamesOf(string $class, string $property): array
    {
        $metadata = new ClassMetadataFactory(new AttributeLoader());
        $names = [(new MetadataAwareNameConverter($metadata))->normalize($property, $class), $property];
        $names[] = \strtolower((string)\preg_replace('/(?<!^)[A-Z]/', '_$0', $property));

        return \array_values(\array_unique($names));
    }

    /**
     * What the validator reports about one object, which it does by throwing.
     *
     * @param AttributeValidator $validator
     * @param MessageInterface $message
     *
     * @return list<Violation>
     */
    private static function violationsOf(AttributeValidator $validator, MessageInterface $message): array
    {
        try {
            $validator->validate($message);
        } catch (ValidationException $e) {
            return $e->getViolations();
        }

        return [];
    }
}

/**
 * A message whose block rule names a property it does not declare, with a field whose rule rejects
 * the value it is built with.
 */
#[IgnoredWhen(property: 'amuont')]
class MisspelledBlockMessage implements MessageInterface
{
    /**
     * @param float $amount
     * @param string $name
     */
    public function __construct(
        /**
         * @var float
         */
        public float $amount = 5.0,

        /**
         * @var string
         */
        #[NotBlank]
        public string $name = '',
    ) {}
}
