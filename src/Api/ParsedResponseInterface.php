<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api;

/**
 * A response a service method returns, which keeps the body it was read from.
 *
 * Only the object the method returns keeps it. Objects inside it and list items don't, including
 * those of a class that some other method returns, such as an address in a list of addresses.
 */
interface ParsedResponseInterface
{
    /**
     * The body of the response, byte for byte as the API sent it, before any parsing.
     *
     * @return string|null null when the object isn't what a method returned: inside another object,
     *         a list item, or built in code without one
     */
    public function getRawResponse(): ?string;
}
