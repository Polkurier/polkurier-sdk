<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log\Attribute;

use Attribute;

/**
 * This field holds a secret: something that grants access, such as a token, a password, a PIN or a
 * one-time code.
 *
 * Its value is replaced in a logged body unless `logSensitiveData` is on, see
 * {@see \Polkurier\Webservice\Api\ConfigInterface::isSensitiveDataLoggingEnabled()}.
 *
 * The mark sits on the field, so what a key means is decided by the class that declares it: `id`
 * is the two-factor token on {@see \Polkurier\Webservice\Dto\Response\TwoFactorToken} and an
 * ordinary record id everywhere else. A field that carries no mark is logged.
 *
 * Applied by {@see \Polkurier\Webservice\Log\SensitiveDataRedactor}.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class Sensitive
{
}
