<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Api\MessageInterface;

/**
 * Builds the validator the SDK checks payloads with by default.
 *
 * One entry, because every message declares its own rules. The attribute engine reads what each one
 * carries: the field rules, the class-level ones, and any validator it names with
 * {@see \Polkurier\Webservice\Validator\Rule\ValidatedBy} for the logic that does not fit an
 * attribute — an address needing an email because it is the sender, an amount needing to be
 * present because the payment is a top-up.
 *
 * What is covered, and what is not, follows one rule: a payload the API would have accepted must
 * never be rejected here. Client-side validation stricter than the service blocks legitimate calls
 * and is worse than none.
 *
 * So the rules mirror what the service is documented to require, plus a few checks for payloads
 * that are well-formed but cannot produce the result the caller meant — a date range that runs
 * backwards and therefore matches nothing, a page size above the documented maximum, a malformed
 * order number in a batch.
 *
 * Not covered, because the client cannot know it: anything per-carrier or per-account. Which
 * services a carrier sells, whether it needs a collection date, what an account's PIN locks are,
 * how long a PIN must be. All of that is readable from `available_carriers`, `get_account_info`
 * and `get_system_info`, and the service is what enforces it.
 *
 * Add to the registry, or pass your own validator to the client, to enforce rules of your own.
 */
class DefaultValidatorRegistryFactory
{
    /**
     * @return ValidatorRegistry
     */
    public static function create(): ValidatorRegistry
    {
        return (new ValidatorRegistry())
            ->register(MessageInterface::class, new AttributeValidator());
    }
}
