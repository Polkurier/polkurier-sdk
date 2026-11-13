<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Common;

/**
 * The two-factor token and code of a message whose operation needs two-factor confirmation.
 *
 * The using class declares the `$twoFactorToken` and `$twoFactorCode` properties. Get the token
 * from {@see \Polkurier\Webservice\Api\Service\TwoFactorTokenServiceInterface::twoFactorTokenRegister()}.
 *
 * @api
 */
trait TwoFactorFieldsTrait
{
    public const string FIELD_TWO_FACTOR_TOKEN = 'two_factor_token';
    public const string FIELD_TWO_FACTOR_CODE = 'two_factor_code';
}
