<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Implements {@see \Polkurier\Webservice\Api\ParsedResponseInterface} for a class whose constructor
 * promotes the body to a `$rawResponse` property.
 */
trait KeepsRawResponseTrait
{
    /**
     * @return string|null
     */
    #[Ignore]
    public function getRawResponse(): ?string
    {
        return $this->rawResponse;
    }
}
