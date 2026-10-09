<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A bank account registered for cash-on-delivery settlement, as stored.
 *
 * To register or update one, use {@see \Polkurier\Webservice\Request\BankAccount\SaveMessage}.
 * It also carries the confirmation the API may require before it changes where money is paid.
 */
readonly class BankAccount implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_DEFAULT = 'default';
    /**-@#*/

    /**
     * @param string|null $id
     * @param string $name
     * @param string $number
     * @param bool $default
     */
    public function __construct(
        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        private ?string $id,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        private string $number,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_DEFAULT)]
        private bool $default = false,
    ) {}

    /**
     * @return string|null null if the response leaves it out
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
    }
}
