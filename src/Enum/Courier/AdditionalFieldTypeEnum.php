<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Courier;

/**
 * How a carrier-specific additional field should be presented and validated.
 *
 * Whatever the type, the value is sent as a string in the order's `additional_fields` map.
 */
enum AdditionalFieldTypeEnum: string
{
    /**
     * Free text.
     */
    case Text = 'TEXT';

    /**
     * One of a fixed set of options, listed on the field.
     */
    case Select = 'SELECT';
}
