<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * A document attached to an order, as the order listing describes it.
 *
 * The listing doesn't carry the contents. Pass the name to
 * {@see \Polkurier\Webservice\Api\Service\OrdersServiceInterface::getOrderFile()} to download it.
 *
 * @api
 */
final readonly class File implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_SIZE = 'size';
    public const string FIELD_HASH = 'hash';
    public const string FIELD_DATE_CREATED = 'date_created';
    /**-@#*/

    /**
     * @param string $name filename, the one getOrderFile() takes
     * @param string $type media type
     * @param int $size size in bytes
     * @param string $hash content hash, stable across downloads
     * @param \DateTimeInterface|null $dateCreated when the document was attached, null if unknown
     */
    public function __construct(
        /**
         * @var string filename, the one getOrderFile() takes
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name = '',

        /**
         * @var string media type
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public string $type = '',

        /**
         * @var int size in bytes
         */
        #[Serializer\SerializedName(self::FIELD_SIZE)]
        public int $size = 0,

        /**
         * @var string content hash, stable across downloads
         */
        #[Serializer\SerializedName(self::FIELD_HASH)]
        public string $hash = '',

        /**
         * @var \DateTimeInterface|null null if the date is unknown
         */
        #[Serializer\SerializedName(self::FIELD_DATE_CREATED)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $dateCreated = null,
    ) {}
}
