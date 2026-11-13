<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Treats empty values as missing, in both directions.
 *
 * When writing, it drops empty fields marked with {@see OmitWhenEmpty}. When reading, it turns an
 * empty array into null, since a missing nested object can arrive that way.
 *
 * It wraps the object normalizer. The wrapped normalizer builds the payload, and this one removes
 * the keys that shouldn't be there. Keys are matched by wire name, since that is what the wrapped
 * normalizer returns.
 *
 * It also leaves out the fields marked {@see ResponseOnly}, and writes the entries of a field marked
 * {@see InlineFields} into the enclosing object.
 *
 * The list of fields is read once per class and cached, to avoid reflection on every request.
 */
class OmitEmptyNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * Wire names of the fields carrying a given attribute, per attribute and class.
     *
     * @var array<class-string, array<class-string, string[]>>
     */
    protected static array $marked = [];

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

        foreach (static::omittedFor($data::class) as $field) {
            if (\array_key_exists($field, $normalized) && static::isEmpty($normalized[$field])) {
                unset($normalized[$field]);
            }
        }

        foreach (static::findFieldsMarkedWith($data::class, ResponseOnly::class) as $field) {
            unset($normalized[$field]);
        }

        $inlined = [];
        foreach (static::findFieldsMarkedWith($data::class, InlineFields::class) as $field) {
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
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // An optional nested object can arrive as an empty array instead of null. Building an
        // object from it would fail on the first required field or invent one that was never sent.
        // So it becomes null, and the receiving property decides whether null is allowed.
        if ($data === [] && !\str_ends_with($type, '[]')) {
            return null;
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
     * The wire names of all fields the class marks to be dropped when empty.
     *
     * Checks both properties and getters, since a field can be computed. Parent classes are
     * included, so a base message's optional fields stay optional in its subclasses.
     *
     * @param class-string $class
     *
     * @return string[]
     */
    protected static function omittedFor(string $class): array
    {
        return static::findFieldsMarkedWith($class, OmitWhenEmpty::class);
    }

    /**
     * The wire names of all fields the class marks with the given attribute.
     *
     * @param class-string $class
     * @param class-string $attributeClass
     *
     * @return string[]
     */
    protected static function findFieldsMarkedWith(string $class, string $attributeClass): array
    {
        if (isset(static::$marked[$attributeClass][$class])) {
            return static::$marked[$attributeClass][$class];
        }

        $fields = [];
        foreach (ClassMembers::readMarkedWith($class, $attributeClass) as $member) {
            $fields[] = WireNameReader::read($member);
        }

        return static::$marked[$attributeClass][$class] = \array_values(\array_unique($fields));
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
