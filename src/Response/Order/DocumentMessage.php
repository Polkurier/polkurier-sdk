<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Response\DecodesFile;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A document for one or more orders (e.g. waybill labels), as a single PDF.
 *
 * @see DocumentMessage
 */
readonly class DocumentMessage implements MessageInterface
{
    use DecodesFile;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_FILE = 'file';
    /**-@#*/

    /**
     * @param string $file base64-encoded PDF
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        private string $file = '',
    ) {}

    /**
     * The document as the API sent it, a base64-encoded PDF. {@see self::getDecodedFile()} returns
     * the raw bytes.
     *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }
}
