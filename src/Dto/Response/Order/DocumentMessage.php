<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Dto\Response\DecodesFileTrait;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A label or handover protocol for one or more orders, as a single PDF, or a label in the printer
 * language asked for.
 */
final readonly class DocumentMessage implements MessageInterface, ParsedResponseInterface
{
    use DecodesFileTrait;
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_FILE = 'file';
    /**-@#*/

    /**
     * @param string $file base64-encoded PDF or printer-language data
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        private string $file = '',

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}

    /**
     * The document as the API sent it, base64-encoded. {@see self::getDecodedFile()} returns the raw
     * bytes.
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }
}
