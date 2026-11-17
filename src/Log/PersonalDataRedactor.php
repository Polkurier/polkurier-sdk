<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use Polkurier\Webservice\Log\Attribute\PersonalData;

/**
 * Hides the fields marked {@see PersonalData} in request and response bodies before they are
 * logged: people's and companies' names, street addresses, email addresses, phone numbers, tax and
 * company registration numbers, and bank account numbers.
 *
 * Identifiers such as the login, an order number and a ticket number carry no mark, so a log entry
 * can still be traced back to the call it came from.
 */
class PersonalDataRedactor extends FieldRedactor
{
    /**
     * @return class-string
     */
    protected function actsOn(): string
    {
        return PersonalData::class;
    }
}
