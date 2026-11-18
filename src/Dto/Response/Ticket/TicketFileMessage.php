<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * An attachment on a ticket message.
 *
 * It comes without its contents. Download the file by this name with
 * {@see \Polkurier\Webservice\Api\Service\TicketsServiceInterface::getTicketMessageFile()}.
 *
 * @api
 */
final readonly class TicketFileMessage implements MessageInterface
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
     * @param \DateTimeInterface|null $dateCreated when it was attached
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name = '',

        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public string $type = '',

        #[Serializer\SerializedName(self::FIELD_SIZE)]
        public int $size = 0,

        #[Serializer\SerializedName(self::FIELD_DATE_CREATED)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $dateCreated = null,
    ) {}
}
