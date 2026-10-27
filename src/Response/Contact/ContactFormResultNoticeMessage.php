<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A notice attached to a submitted contact form's result.
 */
readonly class ContactFormResultNoticeMessage implements MessageInterface
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
     * @param ?\DateTimeInterface $createdAt
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private string $type = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        private string $content = '',

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_CREATED_AT)]
        private ?\DateTimeInterface $createdAt = null,
    ) {}

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
