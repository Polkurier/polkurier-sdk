<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One setting of an external application, as configured for the account in the Polkurier panel.
 *
 * Which settings there are depends on the application, so they come as name and value pairs. A
 * value that isn't a single value, such as a list, is given as its JSON.
 *
 * @api
 */
final readonly class ExternalApplicationSettingMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAME = 'name';
    public const string FIELD_VALUE = 'value';
    /**-@#*/

    /**
     * @param string $name the setting's name
     * @param string|int|float|bool|null $value the setting's value
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
