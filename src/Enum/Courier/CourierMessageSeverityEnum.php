<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Courier;

/**
 * How prominently a carrier notice should be shown.
 *
 * Only affects how the notice is displayed.
 */
enum CourierMessageSeverityEnum: string
{
    case Info = 'INFO';
    case Success = 'SUCCESS';
    case Warning = 'WARNING';
    case Danger = 'DANGER';
}
