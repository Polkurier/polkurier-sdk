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
 * One choice of a drop-down field on a contact form.
 */
readonly class ContactFormOptionMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_VALUE = 'value';
    public const string FIELD_LABEL = 'label';
    public const string FIELD_CONDITION = 'condition';
    /**-@#*/

    /**
     * @param string $value the answer to submit when this choice is picked
     * @param string $label display text, in Polish
     * @param string $condition when the choice is offered, as the form states it. Empty when
     *        it always is
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        private string $value = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_LABEL)]
        private string $label = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONDITION)]
        private string $condition = '',
    ) {}

    /**
     * @return string the answer to submit when this choice is picked
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string when the choice is offered, as the form states it. Empty when it always is
     */
    public function getCondition(): string
    {
        return $this->condition;
    }
}
