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
 * Where a {@see ValueValidatorInterface} judges one value against a format, this judges a payload
 * against itself — the rules that only exist because two fields are related. A date range that has
 * to run forwards, a field required because another field says so.
 *
 * Reaches the message through {@see MessageFields} rather than the object directly, so a rule can
 * name properties without needing them to be public and can report against the wire field name
 * rather than the PHP one.
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
