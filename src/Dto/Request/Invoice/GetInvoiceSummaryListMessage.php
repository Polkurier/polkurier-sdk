<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Invoice;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Invoice\InvoiceSummaryFormatEnum;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies the invoice whose itemised summary to download, and the format to download it in.
 *
 * Use the number as printed on the invoice, slashes included.
 *
 * @api
 */
final readonly class GetInvoiceSummaryListMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_FORMAT = 'format';
    /**-@#*/

    /**
     * @param string $number the invoice number
     * @param InvoiceSummaryFormatEnum $format PDF to read or print, CSV to import
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        #[Validate\NotBlank]
        public string $number,

        #[Serializer\SerializedName(self::FIELD_FORMAT)]
        public InvoiceSummaryFormatEnum $format = InvoiceSummaryFormatEnum::Pdf,
    ) {}
}
