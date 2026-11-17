<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Polkurier\Webservice\Serializer\Attribute\NamedValues;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The contact form for a topic, as fetched to be shown and submitted.
 *
 * Submit it with {@see \Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage}, quoting this
 * form's id and nonce. Both change on every fetch. The nonce works once, within an hour, so fetch
 * the form just before showing it.
 *
 * A topic without a form gives one with {@see self::$hasForm} false and nothing else. A form that
 * can only be filled in on the Polkurier website has {@see self::$webOnly} set, no fields, and
 * its address in {@see self::$href}.
 *
 * @api
 */
final readonly class ContactFormMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_FIELDS = 'fields';
    public const string FIELD_PROPERTIES = 'properties';
    public const string FIELD_STATE = 'state';
    public const string FIELD_NONCE = 'nonce';
    public const string FIELD_HAS_FORM = 'has_form';
    public const string FIELD_WEB_ONLY = 'web_only';
    public const string FIELD_HREF = 'href';
    /**-@#*/

    /**
     * @param string $id the form's id, to quote when submitting it
     * @param ContactFormFieldMessage[] $fields the fields, in display order
     * @param ContactFormValueMessage[] $properties named values the fields' conditions and choices
     *        refer to
     * @param ContactFormValueMessage[] $state starting values by field name
     * @param string $nonce to quote when submitting the form
     * @param bool $hasForm whether the topic has a form
     * @param bool $webOnly whether the form can only be filled in on the Polkurier website
     * @param string $href the form's address on the Polkurier website, when it is web-only
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string the form's id, to quote when submitting it
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id = '',

        /**
         * @var ContactFormFieldMessage[] in display order
         */
        #[Serializer\SerializedName(self::FIELD_FIELDS)]
        public array $fields = [],

        /**
         * @var ContactFormValueMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_PROPERTIES)]
        #[NamedValues(name: ContactFormValueMessage::FIELD_NAME, value: ContactFormValueMessage::FIELD_VALUE)]
        public array $properties = [],

        /**
         * @var ContactFormValueMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_STATE)]
        #[NamedValues(name: ContactFormValueMessage::FIELD_NAME, value: ContactFormValueMessage::FIELD_VALUE)]
        public array $state = [],

        /**
         * @var string to quote when submitting the form
         */
        #[Serializer\SerializedName(self::FIELD_NONCE)]
        #[Sensitive]
        public string $nonce = '',

        /**
         * @var bool whether the topic has a form
         */
        #[Serializer\SerializedName(self::FIELD_HAS_FORM)]
        public bool $hasForm = false,

        /**
         * @var bool whether the form can only be filled in on the Polkurier website
         */
        #[Serializer\SerializedName(self::FIELD_WEB_ONLY)]
        public bool $webOnly = false,

        /**
         * @var string the form's address on the Polkurier website, when it is web-only
         */
        #[Serializer\SerializedName(self::FIELD_HREF)]
        public string $href = '',

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
