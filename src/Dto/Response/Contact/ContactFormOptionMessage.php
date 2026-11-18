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
 * One choice of a drop-down field on a contact form.
 *
 * @api
 */
final readonly class ContactFormOptionMessage implements MessageInterface
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
         * @var string the answer to submit when this choice is picked
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        public string $value = '',

        #[Serializer\SerializedName(self::FIELD_LABEL)]
        public string $label = '',

        /**
         * @var string when the choice is offered, as the form states it. Empty when it always is
         */
        #[Serializer\SerializedName(self::FIELD_CONDITION)]
        public string $condition = '',
    ) {}
}
