<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\Base64;
use Polkurier\Webservice\Serializer\Attribute\Base64DataUrl;
use Polkurier\Webservice\Serializer\Attribute\NamedValues;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Reads the fields whose wire format differs from the value the property holds, as the property
 * declares with {@see Base64}, {@see Base64DataUrl} or {@see NamedValues}.
 *
 * A field that doesn't decode becomes empty and is reported as drift.
 */
class WireFormatDenormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    /**
     * What separates a `data:` URL's media type from its base64 contents.
     */
    protected const string DATA_URL_MARKER = ';base64,';

    /**
     * How much of an undecodable value a drift entry quotes, so a whole file doesn't reach the log.
     */
    protected const int EXCERPT_LENGTH = 32;

    /**
     * The declared formats of each class read so far, by wire name.
     *
     * @var array<string, array<string, Base64|Base64DataUrl|NamedValues>>
     */
    protected static array $formats = [];

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
            $path = $context['deserialization_path'] ?? null;
            $data = $this->read(
                $data,
                $type,
                \is_string($path) ? $path : '',
                ResponseDriftCollector::fromContext($context),
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
     * @param string $type
     * @param string $path where the object sits in the response
     * @param ResponseDriftCollector|null $drift
     *
     * @return array<array-key, mixed>
     */
    protected function read(array $data, string $type, string $path, ?ResponseDriftCollector $drift): array
    {
        foreach (static::formatsOf($type) as $field => $format) {
            if (!\array_key_exists($field, $data)) {
                continue;
            }
            $at = $path === '' ? $field : $path . '.' . $field;
            $data[$field] = match (true) {
                $format instanceof Base64 => self::decodeBase64($data[$field], $at, $drift),
                $format instanceof Base64DataUrl => self::decodeDataUrl($data[$field], $at, $drift),
                $format instanceof NamedValues => self::pairs($data[$field], $format),
            };
        }

        return $data;
    }

    /**
     * @param string $type
     *
     * @return array<string, Base64|Base64DataUrl|NamedValues>
     */
    protected static function formatsOf(string $type): array
    {
        if (isset(static::$formats[$type])) {
            return static::$formats[$type];
        }
        $formats = [];
        if (\class_exists($type)) {
            foreach ((new \ReflectionClass($type))->getProperties() as $property) {
                $attributes = [
                    ...$property->getAttributes(Base64::class),
                    ...$property->getAttributes(Base64DataUrl::class),
                    ...$property->getAttributes(NamedValues::class),
                ];
                if ($attributes === []) {
                    continue;
                }
                // Read from the attribute's argument: its accessor differs across Symfony versions.
                $name = ($property->getAttributes(SerializedName::class)[0] ?? null)?->getArguments()[0] ?? null;
                $wire = \is_string($name) ? $name : $property->getName();
                $formats[$wire] = $attributes[0]->newInstance();
            }
        }

        return static::$formats[$type] = $formats;
    }

    /**
     * @param mixed $value
     * @param string $at
     * @param ResponseDriftCollector|null $drift
     *
     * @return mixed
     */
    private static function decodeBase64(mixed $value, string $at, ?ResponseDriftCollector $drift): mixed
    {
        if (!\is_string($value)) {
            return $value;
        }
        $decoded = \base64_decode($value, true);
        if ($decoded === false) {
            self::reportUndecodable($value, $at, $drift, 'base64');
        }

        return (string)$decoded;
    }

    /**
     * @param mixed $value
     * @param string $at
     * @param ResponseDriftCollector|null $drift
     *
     * @return mixed
     */
    private static function decodeDataUrl(mixed $value, string $at, ?ResponseDriftCollector $drift): mixed
    {
        if (!\is_string($value)) {
            return $value;
        }
        $marker = \strpos($value, static::DATA_URL_MARKER);
        if ($marker === false) {
            self::reportUndecodable($value, $at, $drift, 'base64 data: URL');

            return '';
        }

        return self::decodeBase64(\substr($value, $marker + \strlen(static::DATA_URL_MARKER)), $at, $drift);
    }

    /**
     * @param mixed $value
     * @param NamedValues $format
     *
     * @return mixed
     */
    private static function pairs(mixed $value, NamedValues $format): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }
        $pairs = [];
        foreach ($value as $name => $item) {
            $pairs[] = [
                $format->name => (string)$name,
                $format->value => $item === null || \is_scalar($item)
                    ? $item
                    : (string)\json_encode($item, \JSON_UNESCAPED_UNICODE),
            ];
        }

        return $pairs;
    }

    /**
     * Reports a field that doesn't decode, quoting only its start.
     *
     * @param string $value
     * @param string $at
     * @param ResponseDriftCollector|null $drift
     * @param string $expected
     *
     * @return void
     */
    private static function reportUndecodable(
        string $value,
        string $at,
        ?ResponseDriftCollector $drift,
        string $expected,
    ): void {
        $drift?->add(ResponseDriftTypeEnum::CastValue, $at, \substr($value, 0, static::EXCERPT_LENGTH), $expected);
    }
}
