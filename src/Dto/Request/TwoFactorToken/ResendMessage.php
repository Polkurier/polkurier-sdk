<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\TwoFactorToken;

use Polkurier\Webservice\Dto\Request\Common\TokenMessage;

/**
 * Asks for the code of a two-factor token to be sent again.
 *
 * @api
 */
final readonly class ResendMessage extends TokenMessage
{
}
