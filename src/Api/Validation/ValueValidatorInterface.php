<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Validation;

/**
 * Checks one value in isolation.
 *
 * The narrow half of validation. A value validator knows a format — a postcode, an order number, a
 * bank account number — and nothing else: not which field it came from, not what the rest of the
 * payload says, not whether the field was required in the first place. That is what makes it
 * reusable: the same postcode rule serves a sender, a recipient, a payer address and a city
 * lookup.
 *
 * Because it is pure, it returns its findings rather than recording them. The caller decides which
 * field they belong to. {@see \Polkurier\Webservice\Validator\ViolationPool::check()} does that
 * for the common case.
 *
 * Rules that depend on more than one value belong in a {@see ValidatorInterface} over the whole
 * message instead.
 */
interface ValueValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return string[] reasons the value is unacceptable, phrased to follow the field name;
     *                  empty when there is nothing wrong with it
     */
    public function validate(mixed $value): array;
}
