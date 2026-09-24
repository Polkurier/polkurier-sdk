<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Order;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * @see DocumentMessage
 */
readonly class DocumentMessage implements MessageInterface
{
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
      * The document as the API sent it: base64-encoded PDF. Use {@see self::getDecodedFile()} to get
      * the raw bytes.
      *
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
      * The document's raw bytes, ready to write to disk or stream to a browser.
      *
     * @return string
     */
    public function getDecodedFile(): string
    {
        return (string)\base64_decode($this->file, true);
    }
}
