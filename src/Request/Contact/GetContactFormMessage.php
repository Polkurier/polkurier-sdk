<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Fetches the form definition for one contact category.
 */
readonly class GetContactFormMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_CATEGORY = 'category';
    /**-@#*/

    /**
     * @param string $category the id of a topic from getContactTopics() that has a form
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CATEGORY)]
        #[Validate\NotBlank]
        private string $category,
    ) {}

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }
}
