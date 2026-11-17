<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Fails a read that the {@see ResponseDriftCollector} in its context records a difference for,
 * except an unknown enum value.
 *
 * Strict reading is lenient reading plus this check, so it rejects exactly what lenient reading
 * reports. Everything else goes to the wrapped serializer unchanged.
 */
class StrictResponseSerializer implements SerializerInterface, NormalizerInterface, DenormalizerInterface
{
    /**
     * @param Serializer $inner
     */
    public function __construct(
        /**
         * @var Serializer
         */
        protected readonly Serializer $inner,
    ) {}

    /**
     * @param mixed $data
     * @param string $format
     * @param array<string, mixed> $context
     *
     * @return string
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface when the wrapped
     *         serializer refuses the data
     */
    public function serialize(mixed $data, string $format, array $context = []): string
    {
        return $this->inner->serialize($data, $format, $context);
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string $format
     * @param array<string, mixed> $context
     *
     * @return mixed
     * @throws UnexpectedValueException when the context's collector records a difference
     */
    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        return $this->denormalize($this->inner->decode($data, $format, $context), $type, $format, $context);
    }

    /**
     * @param mixed $data
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return \ArrayObject<string, mixed>|array<array-key, mixed>|string|int|float|bool|null
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface when the wrapped
     *         serializer refuses the data
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
     * @throws UnexpectedValueException when the context's collector records a difference
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $drift = ResponseDriftCollector::fromContext($context);
        $before = $drift === null ? 0 : \count($drift->all());
        $result = $this->inner->denormalize($data, $type, $format, $context);

        $differences = [];
        foreach (\array_slice($drift?->all() ?? [], $before) as $entry) {
            if ($entry->type !== ResponseDriftTypeEnum::UnknownEnumValue) {
                $differences[] = $entry->type->value . ' at "' . $entry->path . '"';
            }
        }
        if ($differences !== []) {
            throw new UnexpectedValueException(
                'The response differs from what the SDK expects: ' . \implode(', ', $differences) . '.',
            );
        }

        return $result;
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
}
