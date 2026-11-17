<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Submits a completed contact form.
 *
 * Take the form id and nonce from the matching
 * {@see \Polkurier\Webservice\Api\Service\TicketsServiceInterface::getContactForm()} response.
 * A nonce works once, within an hour of the fetch. Pass the answers in $fields, keyed by the field
 * names the form definition gives. They are sent next to the category, form id and nonce. A files
 * field takes a list of {@see \Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage}.
 *
 * @api
 */
final readonly class SaveContactFormMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_CATEGORY = 'category';
    public const string FIELD_FORM_ID = 'form_id';
    public const string FIELD_NONCE = 'nonce';
    /**-@#*/

    /**
     * @param string $category the topic id the form was fetched for
     * @param string $formId from the form definition
     * @param string $nonce from the form definition
     * @param array<string, scalar|list<\Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage>> $fields
     *        the answers, keyed by the form's field names; a files field takes a list of attachments
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CATEGORY)]
        #[Validate\NotBlank]
        public string $category,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FORM_ID)]
        #[Validate\NotBlank]
        public string $formId,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NONCE)]
        #[Validate\NotBlank]
        #[Sensitive]
        public string $nonce,

        /**
         * @var array<string, scalar|list<\Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage>>
         */
        #[InlineFields]
        #[PersonalData]
        public array $fields = [],
    ) {}
}
