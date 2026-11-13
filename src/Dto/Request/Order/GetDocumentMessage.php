<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Order\LabelFileFormatEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies the orders to fetch the label or protocol for.
 *
 * Several order numbers come back as one PDF.
 */
final readonly class GetDocumentMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ORDER_NO = 'orderno';
    public const string FIELD_FILE_FORMAT = 'file_format';
    /**-@#*/

    /**
     * @param string[] $orderNo order numbers, at least one
     * @param LabelFileFormatEnum|null $fileFormat printer language for labels instead of PDF. Only
     *        `get_label` uses it, and only for a single order number
     */
    public function __construct(
        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ORDER_NO)]
        #[Validate\Each(Validate\OrderNumber::class)]
        #[Validate\NotEmptyList]
        public array $orderNo,

        /**
         * @var LabelFileFormatEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_FILE_FORMAT)]
        #[OmitWhenEmpty]
        public ?LabelFileFormatEnum $fileFormat = null,
    ) {}
}
