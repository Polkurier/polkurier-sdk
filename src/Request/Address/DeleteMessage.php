<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Address;

use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies an address-book entry to delete.
 *
 * The role is part of the key. An address saved as both sender and recipient is only removed from
 * the role given here.
 */
readonly class DeleteMessage extends GetMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
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
        private string $id,
        AddressTypeEnum $type,
    ) {
        parent::__construct($type);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
