<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log\Attribute;

use Attribute;

/**
 * This field describes a person or a company: a name, an address, an email address, a phone
 * number, a tax or company registration number, a bank account number.
 *
 * Its value is replaced in a logged body unless `logPersonalData` is on, see
 * {@see \Polkurier\Webservice\Api\ConfigInterface::isPersonalDataLoggingEnabled()}.
 *
 * The mark sits on the field, so what a key means is decided by the class that declares it:
 * `number` is a bank account on {@see \Polkurier\Webservice\Entity\BankAccount} and an order
 * number elsewhere. A field that carries no mark is logged.
 *
 * Identifiers are left unmarked on purpose. The login, an order number and a ticket number say
 * which call a log entry belongs to without describing the person behind it.
 *
 * Applied by {@see \Polkurier\Webservice\Log\PersonalDataRedactor}.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class PersonalData
{
}
