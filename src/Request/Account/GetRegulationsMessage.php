<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Account\RegulationsTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Selects which legal document to fetch.
 */
readonly class GetRegulationsMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_TYPE = 'type';
    /**-@#*/

    /**
     * @param RegulationsTypeEnum $type
     */
    public function __construct(
        /**
         * @var RegulationsTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private RegulationsTypeEnum $type = RegulationsTypeEnum::Regulations,
    ) {}

    /**
     * @return RegulationsTypeEnum
     */
    public function getType(): RegulationsTypeEnum
    {
        return $this->type;
    }
}
