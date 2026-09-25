<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Submits a completed contact form.
 *
 * Take the form id and nonce from the matching
 * {@see \Polkurier\Webservice\Api\Service\TicketsServiceInterface::getContactForm()} response.
 * Fetch the form again for each submission.
 */
readonly class SaveContactFormMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_CATEGORY = 'category';
    public const string FIELD_FORM_ID = 'form_id';
    public const string FIELD_NONCE = 'nonce';
    /**-@#*/

    /**
     * @param string $category category slug, matching the form that was fetched
     * @param string $formId from the form definition
     * @param string $nonce from the form definition
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
}
