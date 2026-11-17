<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Dto\Response\ErrorMessage;
use Polkurier\Webservice\Dto\Response\ResponseMessage;

/**
 * A response body after it was read: the envelope the API sent, and how the body differed from
 * what the SDK expects.
 *
 * The drift belongs to this body alone.
 *
 * @api
 */
final readonly class ReadResponse
{
    /**
     * @param ResponseMessage|ErrorMessage $envelope the payload of a successful call, or the
     *        error of a rejected one
     * @param list<ResponseDrift> $drift empty when the body was as expected
     */
    public function __construct(
        public ResponseMessage|ErrorMessage $envelope,
        public array $drift = [],
    ) {}
}
