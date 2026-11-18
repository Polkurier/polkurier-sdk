<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Address;

use Polkurier\Webservice\Dto\Request\Common\IdMessage;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies an address-book entry to delete.
 *
 * The role is part of the key. An address saved as both sender and recipient is only removed from
 * the role given here.
 *
 * @api
 */
final readonly class DeleteMessage extends IdMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'type';
    /**-@#*/

    /**
     * @param string $id
     * @param AddressTypeEnum $type
     */
    public function __construct(
        string $id,

        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public AddressTypeEnum $type,
    ) {
        parent::__construct($id);
    }
}
