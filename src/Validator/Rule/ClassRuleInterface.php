<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator\Rule;

/**
 * A rule declared on the message class, which names the properties it reads.
 *
 * A name is a string, so a misspelled or renamed property would leave the rule reading null and
 * reporting nothing. {@see \Polkurier\Webservice\Validator\AttributeValidator} resolves every name
 * the rules of a class declare against that class, and raises
 * {@see \Polkurier\Webservice\Exception\InvalidArgumentException} for a name the class does not
 * provide.
 */
interface ClassRuleInterface
{
    /**
     * The properties the rule reads, by property name.
     *
     * @return list<string>
     */
    public function listProperties(): array;
}
