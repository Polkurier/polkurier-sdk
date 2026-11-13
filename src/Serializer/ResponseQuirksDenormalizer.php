<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Dto\Response\Carrier\AdditionalData\Property;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormFieldMessage;
use Polkurier\Webservice\Dto\Response\CourierPoint;
use Polkurier\Webservice\Dto\Response\Order\TrackingEventMessage;
use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Repairs the known quirks of the API's responses before they are read, so each property of a
 * response class holds a clean value. Formats the API always uses are declared on the properties
 * instead, see {@see WireFormatDenormalizer}.
 *
 * Each fix applies to one class and the fields it names, nowhere else. A response that differs from
 * what the SDK expects in any other way is read and reported as drift as usual. The fixes describe
 * the responses the SDK has seen; none has been checked against the API's code yet.
 */
class ResponseQuirksDenormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
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
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (\is_array($data)) {
            $data = $this->repair($data, $type);
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
     * Applies the fixes known for the class.
     *
     * @param array<array-key, mixed> $data
     * @param string $type
     *
     * @return array<array-key, mixed>
     */
    protected function repair(array $data, string $type): array
    {
        return match (true) {
            // Paging numbers come as null on some lists. Null means none, as when they are left out.
            \is_subclass_of($type, PagedListMessage::class) => self::dropNulls($data, [
                PagedListMessage::FIELD_TOTAL_ROWS,
                PagedListMessage::FIELD_CURRENT_PAGE,
                PagedListMessage::FIELD_PAGE_SIZE,
                ...(\defined($type . '::FIELD_TOTAL_PAGES') ? [\constant($type . '::FIELD_TOTAL_PAGES')] : []),
            ]),
            // A tracking event may come with a null location or description.
            $type === TrackingEventMessage::class => self::dropNulls($data, [
                TrackingEventMessage::FIELD_LOCATION,
                TrackingEventMessage::FIELD_DESCRIPTION,
            ]),
            // A carrier often sends null for whether an option is on by default.
            $type === Property::class => self::dropNulls($data, [Property::FIELD_DEFAULT]),
            // `options` holds either the choices or the name of the form property listing them.
            $type === ContactFormFieldMessage::class => self::splitOptions($data),
            // A point lists functions specific to its carrier among the general ones, sometimes more
            // than once.
            $type === CourierPoint::class => self::knownFunctions($data),
            // The transport methods may come as an object keyed by method.
            $type === TwoFactorToken::class => self::listOfValues($data, TwoFactorToken::FIELD_AVAILABLE_METHODS),
            default => $data,
        };
    }

    /**
     * Removes the fields that are null, so the property takes its default.
     *
     * @param array<array-key, mixed> $data
     * @param list<string> $fields
     *
     * @return array<array-key, mixed>
     */
    private static function dropNulls(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (\array_key_exists($field, $data) && $data[$field] === null) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Moves a string `options` to the synthetic field the choices reference is read from.
     *
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    private static function splitOptions(array $data): array
    {
        $options = $data[ContactFormFieldMessage::FIELD_OPTIONS] ?? null;
        if (\is_string($options)) {
            $data[ContactFormFieldMessage::FIELD_OPTIONS_SOURCE] = $options;
            unset($data[ContactFormFieldMessage::FIELD_OPTIONS]);
        }

        return $data;
    }


    /**
     * Keeps the functions the SDK knows, each once.
     *
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    private static function knownFunctions(array $data): array
    {
        $functions = $data[CourierPoint::FIELD_FUNCTIONS] ?? null;
        if (\is_array($functions)) {
            $known = \array_filter(
                $functions,
                static fn(mixed $function): bool => \is_string($function)
                    && PointFunctionEnum::tryFrom($function) !== null,
            );
            $data[CourierPoint::FIELD_FUNCTIONS] = \array_values(\array_unique($known));
        }

        return $data;
    }

    /**
     * Turns a keyed object into a list of its values.
     *
     * @param array<array-key, mixed> $data
     * @param string $field
     *
     * @return array<array-key, mixed>
     */
    private static function listOfValues(array $data, string $field): array
    {
        if (\is_array($data[$field] ?? null)) {
            $data[$field] = \array_values($data[$field]);
        }

        return $data;
    }



}
