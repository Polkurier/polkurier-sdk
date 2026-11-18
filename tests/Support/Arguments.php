<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Support;

/**
 * Arguments a test passes on purpose and static analysis would refuse.
 */
final class Arguments
{
    /**
     * Returns the value as `mixed`, for a call whose argument is of the wrong type because that is
     * what the test is about: how the SDK answers an application that gets it wrong.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function widen(mixed $value): mixed
    {
        return $value;
    }
}
