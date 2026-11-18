<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

use Polkurier\Webservice\Validator\MessageFieldReader;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * A rule about a message as a whole, declared on the class.
 *
 * A {@see \Polkurier\Webservice\Validator\Rule\ValueValidatorInterface} checks one value against a
 * format. This checks how fields of one payload relate, e.g. a date range that must run forwards,
 * or a field that is required because of another field's value.
 *
 * The rule reads the message through {@see MessageFieldReader}. Properties don't have to be public, and
 * violations are reported under the wire field name instead of the PHP one.
 */
interface MessageRuleInterface extends ClassRuleInterface
{
    /**
     * @param MessageFieldReader $fields the message being checked
     * @param ViolationPool $violations
     *
     * @return void
     */
    public function validate(MessageFieldReader $fields, ViolationPool $violations): void;
}
