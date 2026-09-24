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
 * Presentation only; it carries no operational meaning beyond the wording of the notice itself.
 */
enum CourierMessageSeverityEnum: string
{
    case Success = 'SUCCESS';
    case Warning = 'WARNING';
    case Danger = 'DANGER';
}
