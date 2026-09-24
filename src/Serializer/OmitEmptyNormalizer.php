<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles the two places where empty means absent.
 *
 * On the way out it drops the fields a class marked {@see OmitWhenEmpty} when they turn out to be
 * empty. On the way in it reads an empty array as no object at all, which is one of the forms an
 * absent nested object arrives in.
 *
 * Wraps the object normalizer rather than replacing it: the wrapped normalizer decides what the
 * payload looks like, and this removes the keys that should not have been in it. The keys it
 * matches on are the wire names, since that is what it is handed back.
 *
 * Which fields those are is read once per class and cached, as reflection over a payload on every
 * request would be the most expensive thing the SDK does.
 */
final class OmitEmptyNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * Wire names to drop when empty, per class.
     *
     * @var array<class-string, string[]>
     */
    private static array $omitted = [];

    /**
     * @param NormalizerInterface&DenormalizerInterface $inner
     */
    public function __construct(
        /**
         * @var NormalizerInterface&DenormalizerInterface
         */
        private readonly NormalizerInterface&DenormalizerInterface $inner,
    ) {}

    /**
     * The wrapped normalizer needs the serializer to reach nested objects, and it is handed to
     * this decorator rather than to it.
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

        foreach (self::omittedFor($data::class) as $field) {
            if (\array_key_exists($field, $normalized) && self::isEmpty($normalized[$field])) {
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
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // An optional nested object can arrive as an empty array rather than as null. Building that
        // into an object would either fail on its first required field or invent one that was never
        // sent, so it becomes null and the property that receives it says whether that is allowed.
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
     * The wire names of every field the class asked to have dropped when empty.
     *
     * Properties and getters both, since a field can be computed — and the hierarchy is walked,
     * so a base message's optional fields stay optional in its subclasses.
     *
     * @param class-string $class
     *
     * @return string[]
     */
    private static function omittedFor(string $class): array
    {
        if (isset(self::$omitted[$class])) {
            return self::$omitted[$class];
        }

        $fields = [];
        // Always the class of an object being normalized, so there is nothing here that can fail.
        $reflection = new \ReflectionClass($class);

        for ($current = $reflection; $current !== false; $current = $current->getParentClass()) {
            $members = [...$current->getProperties(), ...$current->getMethods()];

            foreach ($members as $member) {
                if ($member->getAttributes(OmitWhenEmpty::class) === []) {
                    continue;
                }

                $name = null;
                foreach ($member->getAttributes() as $attribute) {
                    if (\str_ends_with($attribute->getName(), '\\SerializedName')) {
                        $arguments = $attribute->getArguments();
                        $candidate = $arguments[0] ?? $arguments['serializedName'] ?? null;
                        if (\is_string($candidate)) {
                            $name = $candidate;
                        }
                    }
                }

                $fields[] = $name ?? $member->getName();
            }
        }

        return self::$omitted[$class] = \array_values(\array_unique($fields));
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
