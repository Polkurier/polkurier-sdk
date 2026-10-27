<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

/**
 * Decodes the base64 file of a response that carries one.
 */
trait DecodesFile
{
    /**
     * The file as the API sent it, base64-encoded.
     *
     * @return string
     */
    abstract public function getFile(): string;

    /**
     * The file's raw bytes, ready to write to disk or stream to a browser.
     *
     * @return string empty when the file isn't valid base64
     */
    public function getDecodedFile(): string
    {
        return (string)\base64_decode($this->getFile(), true);
    }
}
