<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Keeps the payload to the fields the SDK sends.
 *
 * A record is meant to be subclassed, so a subclass can hold columns of its own. It sends what the
 * SDK declares, plus anything it declares itself with
 * {@see \Symfony\Component\Serializer\Attribute\SerializedName}; a column without one stays on
 * your side. The API rejects or misreads a key it doesn't know, and an order's cover address is
 * printed and charged for as soon as any value in its block is non-empty.
 *
 * A class that inherits nothing from the SDK is written whole: its fields are its own.
 *
 * It runs inside {@see InlineFieldsNormalizer}. A field marked
 * {@see \Polkurier\Webservice\Serializer\Attribute\InlineFields} that holds an object is written
 * through the whole chain, this included, before its keys are moved up, so it is filtered like any
 * other object. One that holds a map is not: its entries are plain values with keys that aren't
 * known in advance.
 *
 * The list of fields is read once per class and cached, to avoid reflection on every request.
 */
class SentFieldsNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * The wire names each class may send, null for a class the SDK doesn't own.
     *
     * @var array<class-string, string[]|null>
     */
    protected static array $declared = [];

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

        $declared = static::readSentFields($data::class);
        if ($declared === null) {
            return $normalized;
        }
        foreach (\array_keys($normalized) as $field) {
            if (!\in_array((string)$field, $declared, true)) {
                unset($normalized[$field]);
            }
        }

        return $normalized;
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
     * The wire names this class may send, or null when the class is none of the SDK's business.
     *
     * An SDK class, and a subclass of one, send what the SDK declares. A subclass adds a field by
     * naming it, so a property or method of its own counts as soon as it carries a serializer
     * name. A class with no SDK ancestor is not filtered at all: its fields are its own.
     *
     * @param class-string $class
     *
     * @return string[]|null
     */
    protected static function readSentFields(string $class): ?array
    {
        if (\array_key_exists($class, static::$declared)) {
            return static::$declared[$class];
        }

        $fromSdk = false;
        foreach ([$class, ...\array_values(\class_parents($class) ?: [])] as $ancestor) {
            $fromSdk = $fromSdk || static::isOwnedBySdk($ancestor);
        }
        if (!$fromSdk) {
            return static::$declared[$class] = null;
        }

        $fields = [];
        foreach (ClassMemberReader::readAll($class) as $member) {
            if (WireNameReader::readDeclared($member) === null
                && (!static::isOwnedBySdk($member->getDeclaringClass()->getName())
                    || !ClassMemberReader::writesAField($member))
            ) {
                continue;
            }
            $fields[] = WireNameReader::read($member);
        }

        return static::$declared[$class] = \array_values(\array_unique($fields));
    }

    /**
     * Whether the SDK owns the class, and so declares what it sends.
     *
     * An anonymous subclass is named after the class it extends, so the namespace alone does not
     * say whose class it is.
     *
     * @param string $class
     *
     * @return bool
     */
    protected static function isOwnedBySdk(string $class): bool
    {
        return \str_starts_with($class, 'Polkurier\\Webservice\\') && !\str_contains($class, '@anonymous');
    }
}
