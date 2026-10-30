<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
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
 * Wire names come from `#[SerializedName]`. The metadata-aware name converter maps them in both
 * directions, so PHP names don't have to follow the API's naming.
 *
 * Collection element types are read from the `@var` docblock on each property (`Pack[]`,
 * `array<string, string>`). The phpstan extractor reads them, because the properties are promoted
 * constructor parameters and it is the extractor that reads their docblocks. Anything the
 * docblock doesn't describe arrives as a plain array.
 *
 * Objects are built through their constructors, so you never get a half-built object that throws on
 * the first getter. A response that differs from what the SDK expects is filled in, see
 * {@see \Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum}, or with
 * {@see SerializerConfigInterface::strictResponses()} on, fails. Each date property declares its
 * own format, since the API uses several.
 *
 * Building the serializer is slow, so share one instance across the factories.
 * {@see \Polkurier\Webservice\PolkurierWebservice} does this. Metadata caching, JSON flags, the
 * timezone for dates and whether a response that differs is an error are set through
 * {@see \Polkurier\Webservice\Serializer\SerializerConfigInterface}. Everything else is fixed by
 * the API.
 */
readonly class SerializerProvider implements SerializerProviderInterface
{
    /**
     * @var SerializerInterface&DenormalizerInterface
     */
    protected SerializerInterface&DenormalizerInterface $serializer;

    /**
     * @param SerializerConfigInterface $config
     */
    public function __construct(SerializerConfigInterface $config = new SerializerConfig())
    {
        $metadata = static::metadataFactory($config);
        $strict = $config->strictResponses();

        $extractor = new PropertyInfoExtractor(
            typeExtractors: [new PhpStanExtractor(), new ReflectionExtractor()],
        );

        $objectNormalizer = new ObjectNormalizer(
            classMetadataFactory: $metadata,
            nameConverter: new MetadataAwareNameConverter($metadata),
            propertyTypeExtractor: $extractor,
            // Without a collector in the context, as when an application calls the serializer
            // itself, strict reading still rejects an unknown field and a missing one.
            defaultContext: [
                AbstractObjectNormalizer::ALLOW_EXTRA_ATTRIBUTES => !$strict,
                AbstractObjectNormalizer::REQUIRE_ALL_PROPERTIES => $strict,
            ],
        );

        $dateTimeContext = [];
        if ($config->getTimezone() !== null) {
            $dateTimeContext[DateTimeNormalizer::TIMEZONE_KEY] = $config->getTimezone();
        }

        $serializer = new Serializer(
            normalizers: [
                // Must come before the object normalizer, which would read a single order as an
                // empty page.
                new SingleRowPageDenormalizer(),
                // Must come before the enum normalizer, so responses read unknown values as null.
                new LenientEnumDenormalizer(),
                new BackedEnumNormalizer(),
                // Must come before the date normalizer, so empty strings never reach it.
                new AbsentDateNormalizer(),
                new LenientDateDenormalizer(new ExactDateTimeNormalizer(new DateTimeNormalizer($dateTimeContext))),
                // Must come before the array normalizer, so unknown enum values never reach it.
                new EnumListDenormalizer(),
                new LenientListDenormalizer(new ArrayDenormalizer()),
                new OmitEmptyNormalizer(new LenientObjectDenormalizer($objectNormalizer, $metadata, $extractor)),
            ],
            encoders: [
                new JsonEncoder(
                    new JsonEncode([JsonEncode::OPTIONS => $config->getJsonEncodeOptions()]),
                ),
            ],
        );

        // Strict reading reads as leniently, then fails on what the lenient reading reported.
        $this->serializer = $strict ? new StrictResponseSerializer($serializer) : $serializer;
    }

    /**
     * Wraps the metadata factory in a cache if a pool was configured.
     *
     * @param SerializerConfigInterface $config
     *
     * @return ClassMetadataFactoryInterface
     */
    protected static function metadataFactory(SerializerConfigInterface $config): ClassMetadataFactoryInterface
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
