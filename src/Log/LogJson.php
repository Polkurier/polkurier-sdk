<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

/**
 * Encodes a body for a log the same way everywhere: readable, with slashes and Polish letters as
 * they are.
 */
final class LogJson
{
    /**
     * @param array<array-key, mixed> $data
     *
     * @return string|null null if it can't be encoded
     */
    public static function encode(array $data): ?string
    {
        $encoded = \json_encode(
            $data,
            \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION,
        );

        return $encoded === false ? null : $encoded;
    }
}
