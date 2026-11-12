<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The contact form for a topic, as fetched to be shown and submitted.
 *
 * Submit it with {@see \Polkurier\Webservice\Request\Contact\SaveContactFormMessage}, quoting this
 * form's id and nonce. Both change on every fetch. The nonce works once, within an hour, so fetch
 * the form just before showing it.
 *
 * A topic without a form gives one with {@see self::hasForm()} false and nothing else. A form that
 * can only be filled in on the Polkurier website has {@see self::isWebOnly()} set, no fields, and
 * its address in {@see self::getHref()}.
 */
readonly class ContactFormMessage implements MessageInterface, ParsedResponseInterface
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
     * @param array<string, mixed> $properties named values the fields' conditions and choices
     *        refer to
     * @param array<string, mixed> $state starting values by field name
     * @param string $nonce to quote when submitting the form
     * @param bool $hasForm whether the topic has a form
     * @param bool $webOnly whether the form can only be filled in on the Polkurier website
     * @param string $href the form's address on the Polkurier website, when it is web-only
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id = '',

        /**
         * @var ContactFormFieldMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_FIELDS)]
        private array $fields = [],

        /**
         * @var array<string, mixed>
         */
        #[Serializer\SerializedName(self::FIELD_PROPERTIES)]
        private array $properties = [],

        /**
         * @var array<string, mixed>
         */
        #[Serializer\SerializedName(self::FIELD_STATE)]
        private array $state = [],

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NONCE)]
        private string $nonce = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_HAS_FORM)]
        private bool $hasForm = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_WEB_ONLY)]
        private bool $webOnly = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_HREF)]
        private string $href = '',

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * @return string the form's id, to quote when submitting it
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return ContactFormFieldMessage[] in display order
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Named values the fields' conditions and choices refer to, e.g. the list a drop-down field's
     * {@see \Polkurier\Webservice\Response\Contact\ContactFormFieldMessage::getOptionsSource()}
     * names.
     *
     * @return list<ContactFormValueMessage>
     */
    public function getProperties(): array
    {
        return ContactFormValueMessage::listOf($this->properties);
    }

    /**
     * Starting values, by field name.
     *
     * @return list<ContactFormValueMessage>
     */
    public function getState(): array
    {
        return ContactFormValueMessage::listOf($this->state);
    }

    /**
     * @return string to quote when submitting the form
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * @return bool whether the topic has a form
     */
    public function hasForm(): bool
    {
        return $this->hasForm;
    }

    /**
     * @return bool whether the form can only be filled in on the Polkurier website
     */
    public function isWebOnly(): bool
    {
        return $this->webOnly;
    }

    /**
     * @return string the form's address on the Polkurier website, when it is web-only
     */
    public function getHref(): string
    {
        return $this->href;
    }
}
