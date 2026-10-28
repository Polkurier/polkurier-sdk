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
 * A label or handover protocol for one or more orders, as a single PDF, or a label in the printer
 * language asked for.
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
     * @param string $file base64-encoded PDF or printer-language data
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_FILE)]
        private string $file = '',
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
