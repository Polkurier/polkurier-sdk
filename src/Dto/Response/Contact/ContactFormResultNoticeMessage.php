<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A notice attached to a submitted contact form's result.
 *
 * @api
 */
final readonly class ContactFormResultNoticeMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'type';
    public const string FIELD_CONTENT = 'content';
    public const string FIELD_CREATED_AT = 'createdAt';
    /**-@#*/

    /**
     * @param string $type `default`, `danger`, `warning`, `info` or `success`
     * @param string $content
     * @param \DateTimeInterface|null $createdAt
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public string $type = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        public string $content = '',

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_CREATED_AT)]
        public ?\DateTimeInterface $createdAt = null,
    ) {}
}
