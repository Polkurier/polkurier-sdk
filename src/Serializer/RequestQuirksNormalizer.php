<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Entity\Order\Address;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Fits a payload to the quirks of the method that receives it, after the object has been written.
 *
 * The counterpart of {@see ResponseQuirksDenormalizer}, which repairs what the API sends. Each fix
 * applies to one class and the fields it names.
 *
 * Only `cover_address` needs one. It is a sender address the carrier prints, so the SDK sends the
 * same record, but `create_order` and `order_valuation_v2`, which read a payload the same way,
 * read that block differently: the flat number
 * under `flat_number`, and nothing else beyond the fields listed in {@see self::COVER_ADDRESS}. A
 * key the block does not use is worse than ignored, because the API treats the cover address as
 * given when any value in it is non-empty, and then charges for printing it. That holds for a
 * field a subclass of the record adds as well, so the block is trimmed to the listed keys whoever
 * declared the rest.
 */
class RequestQuirksNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * The keys `create_order` reads from `cover_address`.
     *
     * @var list<string>
     */
    protected const array COVER_ADDRESS = [
        Address::FIELD_COMPANY,
        Address::FIELD_PERSON,
        Address::FIELD_STREET,
        Address::FIELD_HOUSE_NUMBER,
        Address::FIELD_POSTCODE,
        Address::FIELD_CITY,
        Address::FIELD_PHONE,
        Address::FIELD_EMAIL,
    ];

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

        if ($data instanceof Order && \is_array($normalized)) {
            $cover = $normalized[Order::FIELD_COVER_ADDRESS] ?? null;
            if (\is_array($cover)) {
                $normalized[Order::FIELD_COVER_ADDRESS] = self::trimCoverAddress($cover);
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
     * Keeps the keys the block is read from, under the names it reads them.
     *
     * @param array<array-key, mixed> $cover
     *
     * @return array<array-key, mixed>
     */
    private static function trimCoverAddress(array $cover): array
    {
        $flatNumber = $cover[Address::FIELD_FLAT_NUMBER] ?? null;
        $kept = \array_intersect_key($cover, \array_flip(static::COVER_ADDRESS));

        if ($flatNumber !== null && $flatNumber !== '') {
            $kept[Address::FIELD_COVER_FLAT_NUMBER] = $flatNumber;
        }

        return $kept;
    }
}
