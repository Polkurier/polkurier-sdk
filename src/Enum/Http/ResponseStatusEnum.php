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
 * Use this to check whether a call succeeded. The API returns HTTP 200 for everything, including
 * authentication failures, validation errors and internal errors.
 *
 * Some methods send the value in capitals. The SDK reads it either way.
 */
enum ResponseStatusEnum: string
{
    case Success = 'success';
    case Error = 'error';
}
