<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Courier;

/**
 * How prominently a carrier notice should be shown. It carries no other meaning.
 *
 * @api
 */
enum CourierMessageSeverityEnum: string
{
    case Info = 'INFO';
    case Success = 'SUCCESS';
    case Warning = 'WARNING';
    case Danger = 'DANGER';
}
