<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Common;

/**
 * The wire names of the two-factor token and code, for the messages whose operation needs
 * two-factor confirmation.
 *
 * Get the token from
 * {@see \Polkurier\Webservice\Api\Service\TwoFactorTokenServiceInterface::twoFactorTokenRegister()}.
 *
 * An interface rather than a trait, so the names have an owner that anything can read: PHP refuses
 * to read a trait's constant except through a class that uses it, which would make one of these
 * messages the owner of a name the other two share.
 *
 * @api
 */
interface TwoFactorFieldsInterface
{
    public const string FIELD_TWO_FACTOR_TOKEN = 'two_factor_token';
    public const string FIELD_TWO_FACTOR_CODE = 'two_factor_code';
}
