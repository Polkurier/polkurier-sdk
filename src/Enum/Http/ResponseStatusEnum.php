<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Http;

/**
 * The `status` field every API response carries.
 *
 * This, not the HTTP status, says whether a call succeeded. The API answers HTTP 200 for
 * everything — authentication failures, validation errors and internal faults included.
 */
enum ResponseStatusEnum: string
{
    case Success = 'success';
    case Error = 'error';
}
