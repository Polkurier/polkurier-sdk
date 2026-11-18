<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Courier;

/**
 * Whether a carrier notice is an operational alert or general information.
 *
 * @api
 */
enum CourierMessageTypeEnum: string
{
    /**
     * Something is wrong right now, e.g. delays, outages or suspended collections.
     */
    case Alert = 'ALERT';

    /**
     * General information, not urgent.
     */
    case Message = 'MESSAGE';
}
