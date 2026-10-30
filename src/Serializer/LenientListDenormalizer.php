<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Leaves out of a list of objects the items that aren't objects, and records them.
 *
 * It wraps the array denormalizer and acts when the context holds a {@see ResponseDriftCollector}.
 * A list stays a list.
 */
class LenientListDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    /**
     * @param DenormalizerInterface&DenormalizerAwareInterface $inner
     */
    public function __construct(
        /**
         * @var DenormalizerInterface&DenormalizerAwareInterface
         */
        protected readonly DenormalizerInterface&DenormalizerAwareInterface $inner,
    ) {}

    /**
     * Passes the serializer on to the wrapped denormalizer, which reads each item through it.
     *
     * @param DenormalizerInterface $denormalizer
     *
     * @return void
     */
    public function setDenormalizer(DenormalizerInterface $denormalizer): void
    {
        $this->inner->setDenormalizer($denormalizer);
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
        $drift = ResponseDriftCollector::fromContext($context);
        $class = \substr($type, 0, -2);
        if ($drift !== null && \is_array($data) && \class_exists($class)) {
            $path = $context['deserialization_path'] ?? null;
            $path = \is_string($path) ? $path : '';
            $items = [];
            foreach ($data as $key => $item) {
                if (\is_array($item)) {
                    $items[$key] = $item;
                } else {
                    $drift->add(ResponseDriftTypeEnum::CastValue, $path . '[' . $key . ']', $item, $class);
                }
            }
            $data = \array_is_list($data) ? \array_values($items) : $items;
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
}
