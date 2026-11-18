<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Reads a response object that differs from what the SDK expects, and records how, as
 * {@see ResponseDriftTypeEnum} describes.
 *
 * It wraps the object normalizer and acts when the context holds a {@see ResponseDriftCollector}.
 * Text formats such as CSV carry every value as a string and an empty value as `''`, so there a
 * string that is exactly a number or a boolean is read silently, and `''` counts as null. A context
 * that sets {@see AbstractNormalizer::REQUIRE_ALL_PROPERTIES} to false, as for a form, which leaves
 * out null values, makes a missing field that can be null optional.
 *
 * An object that arrives with fields, but none of the class's, is something else than the class
 * reads, so it fails instead of reading as an object of empty values.
 *
 * A field that can't be null and has no empty value of its type, such as an object or an enum, has
 * nothing to fall back on: the difference is recorded and the read then fails on that field.
 */
class LenientObjectDenormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * The declared fields of each class read so far.
     *
     * @var array<class-string, list<array{
     *     name: string,
     *     wire: string,
     *     types: list<string>,
     *     nullable: bool,
     *     hasDefault: bool,
     *     default: mixed,
     *     ignored: bool,
     *     items: array{types: list<string>, nullable: bool, items: mixed}|null,
     * }>>
     */
    protected static array $fields = [];

    /**
     * @param NormalizerInterface&DenormalizerInterface $inner
     * @param ClassMetadataFactoryInterface $metadata the serializer's mapping, for each field's wire
     *        name and whether it is ignored
     * @param PropertyTypeExtractorInterface $types the serializer's type extractor, for the item type
     *        of each list
     */
    public function __construct(
        /**
         * @var NormalizerInterface&DenormalizerInterface
         */
        protected readonly NormalizerInterface&DenormalizerInterface $inner,

        /**
         * @var ClassMetadataFactoryInterface
         */
        protected readonly ClassMetadataFactoryInterface $metadata,

        /**
         * @var PropertyTypeExtractorInterface
         */
        protected readonly PropertyTypeExtractorInterface $types,
    ) {}

    /**
     * Passes the serializer on to the wrapped normalizer, which needs it for nested objects.
     *
     * @param SerializerInterface $serializer
     *
     * @return void
     */
    public function setSerializer(SerializerInterface $serializer): void
    {
        if ($this->inner instanceof SerializerAwareInterface) {
            $this->inner->setSerializer($serializer);
        }
    }

    /**
     * @param mixed $data
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return \ArrayObject<string, mixed>|array<array-key, mixed>|string|int|float|bool|null
     * @throws SerializerException when the wrapped normalizer refuses the data
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): \ArrayObject|array|string|int|float|bool|null {
        return $this->inner->normalize($data, $format, $context);
    }

    /**
     * @param mixed $data
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return bool
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->inner->supportsNormalization($data, $format, $context);
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return mixed
     * @throws UnexpectedValueException when none of the object's fields arrived
     * @throws SerializerException when the wrapped denormalizer refuses the data
     * @throws InvalidArgumentException when a class on the way declares a field in more than one wire format
     * @throws \TypeError when a constructor refuses what the denormalizer built
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $drift = ResponseDriftCollector::findInContext($context);
        if ($drift !== null && \is_array($data) && \class_exists($type)) {
            $path = $context[ResponseDriftCollector::PATH_KEY] ?? null;
            $path = \is_string($path) ? $path : '';
            $data = $this->tolerate(
                $data,
                $type,
                $drift,
                $path,
                $format === CsvEncoder::FORMAT,
                ($context[AbstractNormalizer::REQUIRE_ALL_PROPERTIES] ?? true) !== false,
                $context[AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS][$type] ?? [],
            );
        }

        return $this->inner->denormalize($data, $type, $format, $context);
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return bool
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = [],
    ): bool {
        return $this->inner->supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * @param string|null $format
     *
     * @return array<class-string|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }

    /**
     * @param array<array-key, mixed> $data
     * @param class-string $class
     * @param ResponseDriftCollector $drift
     * @param string $path
     * @param bool $text whether the data comes from a text format, which carries values as strings
     * @param bool $required whether a field that can be null, and has no default, must be present
     * @param mixed $defaults defaults the context gives fields by name, as
     *        {@see AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS} does
     *
     * @return array<array-key, mixed>
     * @throws UnexpectedValueException when none of the class's fields arrived
     */
    protected function tolerate(
        array $data,
        string $class,
        ResponseDriftCollector $drift,
        string $path,
        bool $text,
        bool $required,
        mixed $defaults,
    ): array {
        $fields = [];
        foreach ($this->readFields($class) as $field) {
            if (\is_array($defaults) && \array_key_exists($field['name'], $defaults)) {
                $field['hasDefault'] = true;
                $field['default'] = $defaults[$field['name']];
            }
            $fields[] = $field;
        }
        $declared = \array_filter($fields, static fn(array $field): bool => !$field['ignored']);
        $arrived = \array_filter($declared, static fn(array $field): bool => \array_key_exists($field['wire'], $data));
        if ($data !== [] && $declared !== [] && $arrived === []) {
            throw new UnexpectedValueException(\sprintf(
                'None of the fields of %s arrived%s.',
                $class,
                $path === '' ? '' : ' at "' . $path . '"',
            ));
        }

        $known = [];
        foreach ($fields as $field) {
            $wire = $field['wire'];
            $known[$wire] = true;
            if ($field['ignored']) {
                continue;
            }

            $at = ResponseDriftCollector::joinPath($path, $field['name']);
            $present = \array_key_exists($wire, $data);
            $value = $present ? $data[$wire] : null;
            $types = $field['types'];
            $expected = $types === [] ? null : \implode('|', $types);
            $single = \count($types) === 1 ? $types[0] : null;

            if ($text && \is_string($value) && static::takesExact($types)) {
                $value = $value === '' ? null : (static::convertExact($value, $types) ?? $value);
                $data[$wire] = $value;
            }

            if ($value === null) {
                if ($field['nullable']) {
                    if ($required && !$present && !$field['hasDefault']) {
                        $drift->add(ResponseDriftTypeEnum::MissingField, $at, null, $expected);
                    }
                    continue;
                }
                if (!$present && $field['hasDefault']) {
                    continue;
                }
                $fallback = $field['hasDefault'] ? $field['default'] : static::makeEmptyValue($single);
                if ($fallback !== null) {
                    $data[$wire] = $fallback;
                }
                $drift->add(ResponseDriftTypeEnum::MissingField, $at, null, $expected);
                continue;
            }

            if (static::fits($value, $types)) {
                if ($field['items'] !== null && \is_array($value)) {
                    $data[$wire] = static::convertItems($value, $field['items'], $drift, $at, $text);
                }
                continue;
            }
            $read = static::convertValue($value, $types) ?? match (true) {
                $field['nullable'] => null,
                $field['hasDefault'] => $field['default'],
                default => static::makeEmptyValue($single),
            };
            // A field that can't be null and has no empty value keeps what arrived: the read fails
            // on it either way, and the message then names the value the response held.
            if ($read !== null || $field['nullable']) {
                $data[$wire] = $read;
            }
            $drift->add(ResponseDriftTypeEnum::CastValue, $at, $value, $expected);
        }

        foreach ($data as $key => $value) {
            if (!isset($known[(string)$key])) {
                $at = ResponseDriftCollector::joinPath($path, (string)$key);
                $drift->add(ResponseDriftTypeEnum::UnknownField, $at, $value);
            }
        }

        return $data;
    }

    /**
     * The items of a list of strings, numbers or booleans, or of lists of them, converted to the item
     * type, without those that can't be. Each one converted or left out is recorded, except a string
     * that is exactly a number or a boolean in a text format. A list stays a list.
     *
     * @param array<array-key, mixed> $items
     * @param array{types: list<string>, nullable: bool, items: mixed} $type the item type, see
     *        {@see self::describeType()}
     * @param ResponseDriftCollector $drift
     * @param string $path
     * @param bool $text
     *
     * @return array<array-key, mixed>
     */
    protected static function convertItems(
        array $items,
        array $type,
        ResponseDriftCollector $drift,
        string $path,
        bool $text,
    ): array {
        $read = [];
        foreach ($items as $key => $item) {
            $at = $path . '[' . $key . ']';
            $nested = $type['items'];
            if ($nested !== null && \is_array($item)) {
                $read[$key] = static::convertItems($item, $nested, $drift, $at, $text);
                continue;
            }
            $types = $type['types'];
            $exact = $text && \is_string($item) && static::takesExact($types)
                ? static::convertExact($item, $types)
                : null;
            if ($exact !== null || ($item === null && $type['nullable'])
                || ($item !== null && $nested === null && static::fits($item, $types))
            ) {
                $read[$key] = $exact ?? $item;
                continue;
            }
            $converted = $nested === null ? static::convertValue($item, $types) : null;
            if ($converted !== null) {
                $read[$key] = $converted;
            }
            $drift->add(ResponseDriftTypeEnum::CastValue, $at, $item, \implode('|', $types));
        }

        return \array_is_list($items) ? \array_values($read) : $read;
    }

    /**
     * Whether a type reads a string from a text format as a number or a boolean.
     *
     * @param list<string> $types
     *
     * @return bool
     */
    protected static function takesExact(array $types): bool
    {
        return !\in_array('string', $types, true) && \array_intersect($types, ['int', 'float', 'bool']) !== [];
    }

    /**
     * Whether the value can be read as the type as it is.
     *
     * @param mixed $value
     * @param list<string> $types the type's members; none for a type that takes anything
     *
     * @return bool
     */
    protected static function fits(mixed $value, array $types): bool
    {
        foreach ($types === [] ? ['mixed'] : $types as $type) {
            $fits = match (true) {
                $type === 'mixed' => true,
                $type === 'string' => \is_string($value),
                $type === 'int' => \is_int($value),
                $type === 'float' => \is_float($value) || \is_int($value),
                $type === 'bool' => \is_bool($value),
                $type === 'array' => \is_array($value),
                \is_subclass_of($type, \BackedEnum::class) => \is_int($value) || \is_string($value),
                \is_a($type, \DateTimeInterface::class, true) => \is_string($value),
                default => \is_array($value),
            };
            if ($fits) {
                return true;
            }
        }

        return false;
    }

    /**
     * The number or boolean a string spells out exactly, as the first member of the type it fits, or
     * null when it doesn't.
     *
     * @param string $value
     * @param list<string> $types
     *
     * @return int|float|bool|null
     */
    protected static function convertExact(string $value, array $types): int|float|bool|null
    {
        foreach ($types as $type) {
            $exact = match ($type) {
                'int' => \filter_var($value, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE),
                'float' => \is_numeric($value) ? (float)$value : null,
                'bool' => match ($value) {
                    'true', '1' => true,
                    'false', '0' => false,
                    default => null,
                },
                default => null,
            };
            if ($exact !== null) {
                return $exact;
            }
        }

        return null;
    }

    /**
     * The value converted to the first simple member of the type it converts to, or null when it
     * can't be.
     *
     * @param mixed $value
     * @param list<string> $types
     *
     * @return string|int|float|bool|null
     */
    protected static function convertValue(mixed $value, array $types): string|int|float|bool|null
    {
        foreach ($types as $type) {
            $converted = match ($type) {
                'string' => match (true) {
                    \is_bool($value) => $value ? 'true' : 'false',
                    \is_int($value), \is_float($value) => (string)$value,
                    default => null,
                },
                'int' => match (true) {
                    \is_float($value), \is_bool($value) => (int)$value,
                    \is_string($value) && \is_numeric($value) => (int)(float)$value,
                    default => null,
                },
                'float' => match (true) {
                    \is_bool($value) => (float)$value,
                    \is_string($value) && \is_numeric($value) => (float)$value,
                    default => null,
                },
                'bool' => match (true) {
                    \is_int($value), \is_float($value) => $value != 0,
                    \is_string($value) => \filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE),
                    default => null,
                },
                default => null,
            };
            if ($converted !== null) {
                return $converted;
            }
        }

        return null;
    }

    /**
     * The empty value of a type, or null for a type that has none.
     *
     * @param string|null $type
     *
     * @return string|int|float|bool|array{}|null
     */
    protected static function makeEmptyValue(?string $type): string|int|float|bool|array|null
    {
        return match ($type) {
            'string' => '',
            'int' => 0,
            'float' => 0.0,
            'bool' => false,
            'array' => [],
            default => null,
        };
    }

    /**
     * The constructor parameters of a class, as the fields of the response it reads.
     *
     * @param class-string $class
     *
     * @return list<array{
     *     name: string,
     *     wire: string,
     *     types: list<string>,
     *     nullable: bool,
     *     hasDefault: bool,
     *     default: mixed,
     *     ignored: bool,
     *     items: array{types: list<string>, nullable: bool, items: mixed}|null,
     * }>
     */
    protected function readFields(string $class): array
    {
        if (isset(static::$fields[$class])) {
            return static::$fields[$class];
        }

        $mapping = $this->metadata->getMetadataFor($class)->getAttributesMetadata();
        $fields = [];
        // The metadata above is read for the same class and fails first, so this can't fail.
        foreach ((new \ReflectionClass($class))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            $members = match (true) {
                $type instanceof \ReflectionNamedType => [$type],
                $type instanceof \ReflectionUnionType => $type->getTypes(),
                default => [],
            };
            $hasDefault = $parameter->isDefaultValueAvailable();
            $items = $this->readItemType($class, $name);
            $fields[] = [
                'name' => $name,
                'wire' => ($mapping[$name] ?? null)?->getSerializedName() ?? $name,
                'types' => \array_values(\array_filter(\array_map(
                    static fn(\ReflectionType $member): ?string => $member instanceof \ReflectionNamedType
                        && $member->getName() !== 'null' ? $member->getName() : null,
                    $members,
                ))),
                'nullable' => $parameter->allowsNull(),
                'hasDefault' => $hasDefault,
                'default' => $hasDefault ? $parameter->getDefaultValue() : null,
                'ignored' => ($mapping[$name] ?? null)?->isIgnored() ?? false,
                'items' => $items,
            ];
        }

        return static::$fields[$class] = $fields;
    }

    /**
     * The item type of a field that is a list of strings, numbers or booleans, or of lists of them,
     * from the field's `@var` docblock.
     *
     * @param class-string $class
     * @param string $property
     *
     * @return array{types: list<string>, nullable: bool, items: mixed}|null see {@see self::describeType()};
     *         null for any other field
     */
    protected function readItemType(string $class, string $property): ?array
    {
        // symfony/property-info 6.4 has no getType(), and its object normalizer doesn't check the
        // items of a list of strings, numbers or booleans, so there is nothing to convert.
        if (!(new \ReflectionClass(PropertyTypeExtractorInterface::class))->hasMethod('getType')) {
            return null;
        }

        $type = $this->types->getType($class, $property);
        foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $member) {
            if ($member instanceof CollectionType) {
                return static::describeType($member->getCollectionValueType());
            }
        }

        return null;
    }

    /**
     * An item type the lenient reading checks: strings, numbers, booleans, or lists of them.
     *
     * @param Type $type
     *
     * @return array{types: list<string>, nullable: bool, items: mixed}|null the type's members
     *         (`string`, `int`, `float`, `bool`, or `array` for a list), whether it can be null, and
     *         for a list the type of its items; null for any other type
     */
    protected static function describeType(Type $type): ?array
    {
        $types = [];
        $nullable = false;
        $items = null;
        foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $part) {
            if ($part instanceof CollectionType) {
                $items = static::describeType($part->getCollectionValueType());
                if ($items === null) {
                    return null;
                }
                $types[] = 'array';
                continue;
            }
            $name = $part instanceof BuiltinType ? $part->getTypeIdentifier()->value : null;
            if ($name === 'null') {
                $nullable = true;
            } elseif (\in_array($name, ['string', 'int', 'float', 'bool'], true)) {
                $types[] = $name;
            } else {
                return null;
            }
        }

        return $types === [] || ($items !== null && $types !== ['array'])
            ? null
            : ['types' => $types, 'nullable' => $nullable, 'items' => $items];
    }
}
