<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\Base64;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A label or handover protocol for one or more orders, as a single PDF, or a label in the printer
 * language asked for.
 */
final readonly class DocumentMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_FILE = 'file';
    /**-@#*/

    /**
     * @param string $file the PDF or printer-language data
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * The document's bytes, decoded from the base64 the API sends.
         *
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        #[Base64]
        public string $file = '',

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
