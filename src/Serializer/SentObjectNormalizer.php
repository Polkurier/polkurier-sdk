<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Keeps the fields marked {@see ResponseOnly}, which the API sends but never reads, out of what is
 * written.
 *
 * They are dropped as members, before a field has a wire name, so a wire name two members share is
 * written by the one that sends it: an order's services are written as a flag map by
 * {@see \Polkurier\Webservice\Entity\Order::getCourierServiceMap()} and read back as their names
 * under that same key. Dropping the field from the finished payload instead would take the written
 * value with it.
 *
 * A member is named as {@see ClassMemberReader::readName()} names it, which is what
 * {@see AbstractNormalizer::IGNORED_ATTRIBUTES} is matched against. A list a caller passes under
 * that key is applied as well, to every object written.
 *
 * Only writing is filtered. Reading a response-only field is the whole reason it exists, so
 * denormalizing passes through untouched.
 */
class SentObjectNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * The context key the caller's own {@see AbstractNormalizer::IGNORED_ATTRIBUTES} is kept under.
     *
     * What the object normalizer is handed holds the caller's members and the class's together,
     * so the caller's cannot be read back out of it for the next object.
     */
    protected const CALLER_IGNORED = 'polkurier_caller_ignored_attributes';

    /**
     * The members each class does not send, by class.
     *
     * @var array<class-string, list<string>>
     */
    protected static array $responseOnly = [];

    /**
     * @param NormalizerInterface&DenormalizerInterface $inner the object normalizer
     */
    public function __construct(
        /**
         * @var NormalizerInterface&DenormalizerInterface
         */
        protected readonly NormalizerInterface&DenormalizerInterface $inner,
    ) {}

    /**
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
        if (\is_object($data)) {
            // A nested object is written through this normalizer too, and what its parent does not
            // send says nothing about what it does, so the two lists are held apart: the caller's
            // under a key of this normalizer's own, to reach every object in the tree, the class's
            // only in what the object normalizer is handed for this one object.
            $given = $context[static::CALLER_IGNORED] ?? $context[AbstractNormalizer::IGNORED_ATTRIBUTES] ?? [];
            $caller = \is_array($given) ? \array_values($given) : [];

            $context[static::CALLER_IGNORED] = $caller;
            $context[AbstractNormalizer::IGNORED_ATTRIBUTES] = [
                ...$caller,
                ...static::readResponseOnly($data::class),
            ];
        }

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
     * The members of a class that are only ever read, read once per class.
     *
     * @param class-string $class
     *
     * @return list<string>
     */
    protected static function readResponseOnly(string $class): array
    {
        if (isset(static::$responseOnly[$class])) {
            return static::$responseOnly[$class];
        }

        $members = [];
        foreach (ClassMemberReader::readMarkedWith($class, ResponseOnly::class) as $member) {
            $members[] = ClassMemberReader::readName($member);
        }

        return static::$responseOnly[$class] = \array_values(\array_unique($members));
    }
}
