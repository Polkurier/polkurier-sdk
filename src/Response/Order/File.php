<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A document attached to an order, as the order listing describes it.
 *
 * The listing doesn't carry the contents. Pass the name to
 * {@see \Polkurier\Webservice\Api\Service\OrdersServiceInterface::getOrderFile()} to download it.
 */
readonly class File implements MessageInterface
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
     * @param ?\DateTimeInterface $dateCreated when the document was attached, null if unknown
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private string $type = '',

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_SIZE)]
        private int $size = 0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_HASH)]
        private string $hash = '',

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE_CREATED)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $dateCreated = null,
    ) {}

    /**
     * @return string filename, the one getOrderFile() takes
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string media type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int size in bytes
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return string content hash, stable across downloads
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return ?\DateTimeInterface null if the date is unknown
     */
    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }
}
