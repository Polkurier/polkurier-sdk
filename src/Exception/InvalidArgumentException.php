<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

/**
 * A value the SDK rejected without asking the API.
 *
 * Thrown when a builder lacks a required field, a payload can't be serialized, a validation rule
 * names the wrong kind of class, or a webhook body isn't an order event. Its subclass
 * {@see \Polkurier\Webservice\Exception\ValidationException} is a payload that breaks the
 * validation rules.
 *
 * @api
 */
class InvalidArgumentException extends Exception
{
}
