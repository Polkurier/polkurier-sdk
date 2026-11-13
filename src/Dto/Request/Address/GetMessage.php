<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Address;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Address\AddressTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Filters an address-book listing by role.
 */
final readonly class GetMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'type';
    /**-@#*/

    /**
     * @param AddressTypeEnum $type
     */
    public function __construct(
        /**
         * @var AddressTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private AddressTypeEnum $type,
    ) {}

    /**
     * @return AddressTypeEnum
     */
    public function getType(): AddressTypeEnum
    {
        return $this->type;
    }
}
