<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Validation;

use Polkurier\Webservice\Validator\MessageFields;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * A rule about a message as a whole, declared on the class.
 *
 * A {@see \Polkurier\Webservice\Api\Validation\ValueValidatorInterface} checks one value against a
 * format. This checks how fields of one payload relate, e.g. a date range that must run forwards,
 * or a field that is required because of another field's value.
 *
 * The rule reads the message through {@see MessageFields}. Properties don't have to be public, and
 * violations are reported under the wire field name instead of the PHP one.
 */
interface MessageRuleInterface
{
    /**
     * @param MessageFields $fields the message being checked
     * @param ViolationPool $violations
     *
     * @return void
     */
    public function validate(MessageFields $fields, ViolationPool $violations): void;
}
