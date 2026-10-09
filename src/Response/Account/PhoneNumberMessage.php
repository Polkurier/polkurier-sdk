<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One telephone number on the account.
 *
 * Removed numbers are not listed. Exactly one number is the default. It receives two-factor codes
 * and carrier notifications.
 */
readonly class PhoneNumberMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_NAME = 'name';
    public const string FIELD_DEFAULT = 'default';
    /**-@#*/

    /**
     * @param string $id opaque identifier, used when deleting
     * @param string $number digits only
     * @param string $name the customer's own label for the number, often empty
     * @param bool $default whether this is the account's primary number
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        private string $number = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name = '',

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }
}
