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
 * The registry has a single entry, the {@see AttributeValidator}, because each message declares
 * its own rules. It runs the field rules, the class-level rules, and any validator the message
 * names with {@see \Polkurier\Webservice\Validator\Rule\ValidatedBy}. Those validators hold the
 * logic that doesn't fit an attribute, e.g. an address that needs an email because it is the
 * sender, or an amount that is required because the payment is a top-up.
 *
 * The rules should never reject a payload the API would accept. They follow what the API is
 * documented to require, plus a few checks for payloads that are well-formed but can't do what
 * the caller meant: a date range that runs backwards and so matches nothing, a page size above
 * the documented maximum, a malformed order number in a batch.
 *
 * Per-carrier and per-account rules are not checked here. That covers which services a carrier
 * offers, whether it needs a collection date, an account's PIN locks and the required PIN length.
 * You can read all of that from `available_carriers`, `get_account_info` and `get_system_info`,
 * and the API checks these rules itself.
 *
 * To add your own rules, register them in the registry or pass your own validator to the client.
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
