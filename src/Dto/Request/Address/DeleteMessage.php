<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Address;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies an address-book entry to delete.
 *
 * The role is part of the key. An address saved as both sender and recipient is only removed from
 * the role given here.
 */
final readonly class DeleteMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_TYPE = 'type';
    /**-@#*/

    /**
     * @param string $id
     * @param AddressTypeEnum $type
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[Validate\NotBlank]
        public string $id,

        /**
         * @var AddressTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public AddressTypeEnum $type,
    ) {}
}
