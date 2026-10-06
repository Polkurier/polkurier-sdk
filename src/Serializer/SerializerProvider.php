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
 * Wire names come from `#[SerializedName]`. The metadata-aware name converter maps them in both
 * directions, so PHP names don't have to follow the API's naming.
 *
 * Collection element types are read from the `@var` docblock on each property (`Pack[]`,
 * `array<string, string>`). The phpstan extractor reads them, because the properties are promoted
 * constructor parameters and it is the extractor that reads their docblocks. Anything the
 * docblock doesn't describe arrives as a plain array.
 *
 * Objects are built through their constructors, so a response is either complete or an error.
 * You never get a half-built object that throws on the first getter. Each date property declares
 * its own format, since the API uses several.
 *
 * Building the serializer is slow, so share one instance across the factories.
 * {@see \Polkurier\Webservice\PolkurierWebservice} does this. Metadata caching, JSON flags, the
 * timezone for dates and whether unknown response fields are an error are set through
 * {@see SerializerConfigInterface}. Everything else is fixed by the API.
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
                // Must come before the object normalizer, which would read a single order as an
                // empty page.
                new SingleRowPageDenormalizer(),
                // Must come before the enum normalizer, so responses read unknown values as null.
                new LenientEnumDenormalizer(),
                new BackedEnumNormalizer(),
                // Must come before the date normalizer, so empty strings never reach it.
                new AbsentDateNormalizer(),
                new DateTimeNormalizer($dateTimeContext),
                // Must come before the array normalizer, so unknown enum values never reach it.
                new EnumListDenormalizer(),
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
