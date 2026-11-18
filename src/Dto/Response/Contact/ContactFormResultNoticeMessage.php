<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * A notice attached to a submitted contact form's result.
 *
 * The API has no path that attaches one, so `save_contact_form` answers without the key this
 * message is read from. A field's own severity is
 * {@see \Polkurier\Webservice\Enum\Contact\ContactFormSeverityEnum}, a different set.
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
     * @param string $type the notice's kind
     * @param string $content
     * @param \DateTimeInterface|null $createdAt
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public string $type = '',

        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        public string $content = '',

        #[Serializer\SerializedName(self::FIELD_CREATED_AT)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $createdAt = null,
    ) {}
}
