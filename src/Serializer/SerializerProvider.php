<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\CacheClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Builds and holds the serializer the SDK maps requests and responses with.
 *
 * The wire names come from `#[SerializedName]` and are resolved in both directions by the
 * metadata-aware name converter, which is what keeps the PHP side free of the API's own naming.
 *
 * Element types for collections are read from the `@var` docblock on each property — `Pack[]`,
 * `array<string, string>` — rather than from a second set of type declarations. The phpstan
 * extractor does that reading, because the properties here are promoted constructor parameters
 * and it is the one that looks at their docblocks. Anything the docblock does not describe
 * arrives as a plain array.
 *
 * Objects are built through their constructors, so a response is either complete or an error; it
 * is never a half-populated object that throws on the first getter. Dates carry their format per
 * property, since the API uses several.
 *
 * Construction is not cheap, so share one instance across the factories; the facade does. What an
 * application can vary — metadata caching, the JSON flags, a timezone to read dates into, whether
 * an unrecognised response field is an error — comes from
 * {@see SerializerConfigInterface}; everything the protocol fixes does not.
 */
final readonly class SerializerProvider implements SerializerProviderInterface
{
    /**
     * @var SerializerInterface&DenormalizerInterface
     */
    private SerializerInterface&DenormalizerInterface $serializer;

    /**
     * @param SerializerConfigInterface $config
     */
    public function __construct(SerializerConfigInterface $config = new SerializerConfig())
    {
        $metadata = self::metadataFactory($config);

        $extractor = new PropertyInfoExtractor(
            typeExtractors: [new PhpStanExtractor(), new ReflectionExtractor()],
        );

        $objectNormalizer = new ObjectNormalizer(
            classMetadataFactory: $metadata,
            nameConverter: new MetadataAwareNameConverter($metadata),
            propertyTypeExtractor: $extractor,
            defaultContext: [
                AbstractObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES => !$config->rejectUnknownFields(),
            ],
        );

        $dateTimeContext = [];
        if ($config->getTimezone() !== null) {
            $dateTimeContext[DateTimeNormalizer::TIMEZONE_KEY] = $config->getTimezone();
        }

        $this->serializer = new Serializer(
            normalizers: [
                new BackedEnumNormalizer(),
                // Ahead of the date normalizer, which is what it exists to spare an empty string.
                new AbsentDateNormalizer(),
                new DateTimeNormalizer($dateTimeContext),
                new ArrayDenormalizer(),
                new OmitEmptyNormalizer($objectNormalizer),
            ],
            encoders: [
                new JsonEncoder(
                    new JsonEncode([JsonEncode::OPTIONS => $config->getJsonEncodeOptions()]),
                ),
            ],
        );
    }

    /**
     * Wraps the metadata factory in a cache when the application supplied a pool.
     *
     * @param SerializerConfigInterface $config
     *
     * @return ClassMetadataFactoryInterface
     */
    private static function metadataFactory(SerializerConfigInterface $config): ClassMetadataFactoryInterface
    {
        $factory = new ClassMetadataFactory(new AttributeLoader());
        $cache = $config->getMetadataCache();

        return $cache === null ? $factory : new CacheClassMetadataFactory($factory, $cache);
    }

    /**
     * {@inheritDoc}
     *
     * @return SerializerInterface&DenormalizerInterface
     */
    public function getSerializer(): SerializerInterface&DenormalizerInterface
    {
        return $this->serializer;
    }
}
