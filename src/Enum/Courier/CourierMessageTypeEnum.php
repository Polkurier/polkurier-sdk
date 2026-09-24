<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Courier;

/**
 * Whether a carrier notice is an operational alert or general information.
 */
enum CourierMessageTypeEnum: string
{
    /**
     * Something is wrong right now — delays, outages, suspended collections.
     */
    case Alert = 'ALERT';

    /**
     * General information with no operational urgency.
     */
    case Message = 'MESSAGE';
}
