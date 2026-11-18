<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer\Attribute;

use Attribute;

/**
 * The API sends this field as values keyed by name. The property holds a list of name and value
 * pairs. A value that isn't a single value, such as a list, is given as its JSON.
 *
 * Applied by {@see \Polkurier\Webservice\Serializer\WireFormatDenormalizer}.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class NamedValues
{
    /**
     * @param string $name the wire name of a pair's name
     * @param string $value the wire name of a pair's value
     */
    public function __construct(
        /**
         * @var string the wire name of a pair's name
         */
        public string $name,

        /**
         * @var string the wire name of a pair's value
         */
        public string $value,
    ) {}
}
