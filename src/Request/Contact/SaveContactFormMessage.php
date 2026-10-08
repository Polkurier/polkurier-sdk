<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Submits a completed contact form.
 *
 * Take the form id and nonce from the matching
 * {@see \Polkurier\Webservice\Api\Service\TicketsServiceInterface::getContactForm()} response.
 * Fetch the form again for each submission. Pass the answers in $fields, keyed by the field names
 * the form definition gives. They are sent next to the category, form id and nonce.
 */
readonly class SaveContactFormMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_CATEGORY = 'category';
    public const string FIELD_FORM_ID = 'form_id';
    public const string FIELD_NONCE = 'nonce';
    public const string FIELD_FIELDS = 'fields';
    /**-@#*/

    /**
     * @param string $category the topic id the form was fetched for
     * @param string $formId from the form definition
     * @param string $nonce from the form definition
     * @param array<string, scalar> $fields the answers, keyed by the form's field names
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CATEGORY)]
        #[Validate\NotBlank]
        private string $category,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FORM_ID)]
        #[Validate\NotBlank]
        private string $formId,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NONCE)]
        #[Validate\NotBlank]
        private string $nonce,

        /**
         * @var array<string, scalar>
         */
        #[Serializer\SerializedName(self::FIELD_FIELDS)]
        #[InlineFields]
        private array $fields = [],
    ) {}

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getFormId(): string
    {
        return $this->formId;
    }

    /**
     * @return string
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * @return array<string, scalar>
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
