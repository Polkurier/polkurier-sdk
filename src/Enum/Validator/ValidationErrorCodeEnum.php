<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Validator;

/**
 * What is wrong with a field, as a value that does not change.
 *
 * The English sentence a violation carries is not contract: it is worded to follow the field name
 * and it names values inside itself, so it cannot be matched on. This is what an integration keys
 * a translation by, with {@see \Polkurier\Webservice\Validator\Violation::$parameters} supplying
 * the numbers and names the sentence spells out.
 *
 * A rule that rejects a value for several different reasons reports a different code for each, so
 * one field can carry more than one code and a code does not identify which rule ran.
 *
 * @api
 */
enum ValidationErrorCodeEnum: string
{
    /**
     * Required, and it arrived empty.
     *
     * A field required in every payload carries no parameters. One required because of another
     * field carries `field`, that field's wire name, and `condition`, which says what about it
     * made this one required: `set`, `not_set`, `empty`, or `equals` with the value in `equals`.
     * A backed enum appears there as its backing value, and a condition that compares against
     * something with no scalar form leaves `equals` out.
     *
     * `field` is a wire name, not a path: it names a field beside this one, under the same parent
     * as the violation's own path.
     *
     * A field required because any member of a fixed group was given carries `condition` as
     * `any_set` and no `field`: every member of the group makes every other one required, so no
     * single one of them is the trigger.
     */
    case Blank = 'blank';

    /**
     * Not an email address. No parameters.
     */
    case NotAnEmail = 'not_an_email';

    /**
     * Not a postcode of its country. Parameter: `country`, the ISO code checked against.
     */
    case NotAPostcode = 'not_a_postcode';

    /**
     * Not an order number. No parameters.
     */
    case NotAnOrderNumber = 'not_an_order_number';

    /**
     * Not a phone number at all, e.g. not a string. No parameters.
     */
    case NotAPhoneNumber = 'not_a_phone_number';

    /**
     * Fewer digits than any phone number has. Parameter: `minimum`.
     */
    case PhoneNumberTooShort = 'phone_number_too_short';

    /**
     * Not a bank account number at all. No parameters.
     */
    case NotABankAccount = 'not_a_bank_account';

    /**
     * A bank account number with characters that cannot appear in one. No parameters.
     */
    case BankAccountBadCharacters = 'bank_account_bad_characters';

    /**
     * A bank account number of the wrong length for its country.
     *
     * Parameters: `country`, `expected`, the number of characters that country uses.
     */
    case BankAccountWrongLength = 'bank_account_wrong_length';

    /**
     * A bank account number of another country than the field accepts. Parameter: `country`.
     */
    case BankAccountWrongCountry = 'bank_account_wrong_country';

    /**
     * A bank account number whose check digits do not match it. No parameters.
     */
    case BankAccountBadChecksum = 'bank_account_bad_checksum';

    /**
     * Not a whole number. No parameters.
     */
    case NotAWholeNumber = 'not_a_whole_number';

    /**
     * Not a number. No parameters.
     */
    case NotANumber = 'not_a_number';

    /**
     * Outside the range of numbers the field takes.
     *
     * Parameters: `minimum` and `maximum`, each present only when the field has that bound, and
     * `exclusiveMinimum`, true when the minimum itself is not allowed either. A field that must
     * be above zero reports `minimum` 0 with `exclusiveMinimum` true; one that may be zero
     * reports `minimum` 0 alone; a page size reports both bounds.
     */
    case OutOfRange = 'out_of_range';

    /**
     * Not a list. No parameters.
     */
    case NotAList = 'not_a_list';

    /**
     * A list with no usable entry. No parameters.
     */
    case EmptyList = 'empty_list';

    /**
     * Not of the type the field holds.
     *
     * Parameters: `type`, the name the message prints, which for a class is its short name;
     * `declared`, the name the rule was given, which for a class is the fully qualified one.
     */
    case WrongType = 'wrong_type';

    /**
     * Later than the field it must not come after.
     *
     * Parameter: `field`, that field's wire name, not a path: it names a field beside this one,
     * under the same parent as the violation's own path.
     */
    case OutOfOrder = 'out_of_order';

    /**
     * Wrong in a way no other case names, reported by a rule written for one message.
     *
     * The violation's English is all there is; show it rather than translating the code. A fault
     * that turns out to be worth translating gets a case of its own in a later release.
     */
    case Other = 'other';
}
