<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Moves the entries of a field marked {@see InlineFields} into the enclosing object, and puts them
 * back when reading.
 *
 * `create_order` takes an order's own fields, not an `order` key holding them, so the field writes
 * its entries into the payload around it and disappears. Read back, a field that holds a record
 * takes the keys that record declares, and one that holds a map takes what the enclosing object
 * doesn't claim itself.
 *
 * A map cannot say which keys are its own, so every key the enclosing object doesn't claim goes
 * into it, in a response too: a key the SDK doesn't know is absorbed there instead of being
 * reported as drift by {@see LenientObjectDenormalizer}. No response class declares a map-valued
 * field.
 *
 * It wraps {@see OmitEmptyNormalizer}, so a field dropped for being empty is dropped before its
 * entries would be moved up.
 *
 * The fields of each class are read once and cached, to avoid reflection on every request.
 */
class InlineFieldsNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * The fields each class inlines, read once.
     *
     * @var array<string, array<string, class-string|null>>
     */
    protected static array $inlined = [];

    /**
     * The names each class writes into the payload around it, read once.
     *
     * @var array<string, string[]>
     */
    protected static array $written = [];

    /**
     * @param NormalizerInterface&DenormalizerInterface $inner
     */
    public function __construct(
        /**
         * @var NormalizerInterface&DenormalizerInterface
         */
        protected readonly NormalizerInterface&DenormalizerInterface $inner,
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
        $normalized = $this->inner->normalize($data, $format, $context);

        if (!\is_object($data) || !\is_array($normalized)) {
            return $normalized;
        }

        $inlined = [];
        foreach (\array_keys(static::readInlinedMembers($data::class)) as $field) {
            $entries = $normalized[$field] ?? [];
            unset($normalized[$field]);
            if (\is_array($entries)) {
                $inlined += $entries;
            }
        }

        return $normalized + $inlined;
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
     * @throws SerializerException when the wrapped denormalizer refuses the data
     * @throws InvalidArgumentException when a class on the way declares a field in more than one wire format
     * @throws \TypeError when a constructor refuses what the denormalizer built
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (\is_array($data)) {
            $data = static::nestInlined($data, $type);
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
     * Puts the keys of an inlined field back under it, the way they were written.
     *
     * @param array<array-key, mixed> $data
     * @param string $type
     *
     * @return array<array-key, mixed>
     */
    protected static function nestInlined(array $data, string $type): array
    {
        $inlined = static::readInlinedMembers($type);
        if ($inlined === []) {
            return $data;
        }

        $enclosing = \array_diff(WireNameReader::readNames($type), \array_keys($inlined));

        foreach ($inlined as $field => $class) {
            if (\array_key_exists($field, $data)) {
                continue;
            }
            $unclaimed = \array_diff(\array_keys($data), $enclosing);
            $taken = $class === null ? $unclaimed : \array_intersect($unclaimed, static::readWrittenNames($class));
            if ($taken === []) {
                continue;
            }
            $nested = [];
            foreach ($taken as $key) {
                $nested[$key] = $data[$key];
                unset($data[$key]);
            }
            $data[$field] = $nested;
        }

        return $data;
    }

    /**
     * The names a class writes into the payload around it.
     *
     * A record that inlines a record of its own writes that one's names too, and none of its own
     * for the field holding it, so a payload several levels deep is claimed by the class it
     * belongs to rather than left for no one.
     *
     * @param string $class
     *
     * @return string[]
     */
    protected static function readWrittenNames(string $class): array
    {
        if (isset(static::$written[$class])) {
            return static::$written[$class];
        }

        $inlined = static::readInlinedMembers($class);
        if ($inlined === []) {
            return static::$written[$class] = WireNameReader::readNames($class);
        }

        // The class's own names, without the fields whose keys are not written under their own
        // name. Set before recursing, so a record that inlines its own type ends the walk on a
        // set that is already correct rather than on one still holding that field's key.
        $names = \array_diff(WireNameReader::readNames($class), \array_keys($inlined));
        static::$written[$class] = \array_values($names);

        foreach ($inlined as $nested) {
            if ($nested !== null) {
                $names = [...$names, ...static::readWrittenNames($nested)];
            }
        }

        return static::$written[$class] = \array_values(\array_unique($names));
    }

    /**
     * The fields a class inlines, as wire name to the class each holds, null where it holds a map.
     *
     * @param string $type
     *
     * @return array<string, class-string|null>
     */
    protected static function readInlinedMembers(string $type): array
    {
        if (isset(static::$inlined[$type])) {
            return static::$inlined[$type];
        }

        $members = [];
        foreach (ClassMemberReader::readMarkedWith($type, InlineFields::class) as $member) {
            $wire = WireNameReader::read($member);
            // The most derived declaration comes first and is the one that holds the value.
            if (\array_key_exists($wire, $members)) {
                continue;
            }
            $declared = $member instanceof \ReflectionMethod ? $member->getReturnType() : $member->getType();
            $name = $declared instanceof \ReflectionNamedType ? $declared->getName() : null;
            $members[$wire] = $name !== null && \class_exists($name) ? $name : null;
        }

        return static::$inlined[$type] = $members;
    }
}
