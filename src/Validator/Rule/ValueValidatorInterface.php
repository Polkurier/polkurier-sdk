<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

/**
 * Checks one value in isolation.
 *
 * A value validator knows one format (a postcode, an order number, a bank account number). It
 * doesn't know which field the value came from, what else is in the payload, or whether the field
 * was required. So the same postcode rule works for a sender, a recipient, a payer address and a
 * city lookup.
 *
 * It returns what it found and the caller decides which field that belongs to.
 * {@see \Polkurier\Webservice\Validator\ViolationPool::check()} handles the usual case.
 *
 * Rules that depend on more than one value go in a
 * {@see \Polkurier\Webservice\Api\Validation\ValidatorInterface} for the whole message.
 */
interface ValueValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return string[] reasons the value is invalid, worded to follow the field name. Empty if the
     *                  value is fine
     */
    public function validate(mixed $value): array;
}
