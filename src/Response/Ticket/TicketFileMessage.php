<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An attachment on a ticket message.
 *
 * The contents are included only when the listing asked for them. Otherwise, fetch the file
 * separately by name.
 */
readonly class TicketFileMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_SIZE = 'size';
    public const string FIELD_DATE_CREATED = 'date_created';
    /**-@#*/

    /**
     * @param string $name filename, and the handle used to download it
     * @param string $type media type
     * @param int $size size in bytes
     * @param ?\DateTimeInterface $dateCreated when it was attached
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
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE_CREATED)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $dateCreated = null,
    ) {}

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }
}
