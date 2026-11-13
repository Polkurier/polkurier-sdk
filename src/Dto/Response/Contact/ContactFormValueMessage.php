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
 * A named value on a contact form: one of the form's properties or starting values, or one of a
 * field's display properties.
 *
 * Their names vary per form, so they come as name and value pairs. A value that isn't a single
 * value, such as a list, is given as its JSON.
 */
final readonly class ContactFormValueMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAME = 'name';
    public const string FIELD_VALUE = 'value';
    /**-@#*/

    /**
     * @param string $name
     * @param string|int|float|bool|null $value a list or other compound value as its JSON
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name,

        /**
         * @var string|int|float|bool|null a list or other compound value as its JSON
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        public string|int|float|bool|null $value,
    ) {}
}
