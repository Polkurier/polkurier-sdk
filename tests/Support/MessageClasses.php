<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Support;

/**
 * The SDK's request and response classes, found by scanning the source, so a new class is covered
 * by every test that iterates them.
 *
 * An entity is both sent and read, so it counts as a request and as a response class.
 */
final class MessageClasses
{
    /**
     * @return array<string, class-string> keyed by the class name without the SDK's namespace
     */
    public static function requests(): array
    {
        return SourceClasses::instantiableUnder('Dto/Request') + SourceClasses::instantiableUnder('Entity');
    }

    /**
     * @return array<string, class-string> keyed by the class name without the SDK's namespace
     */
    public static function responses(): array
    {
        return SourceClasses::instantiableUnder('Dto/Response') + SourceClasses::instantiableUnder('Entity');
    }
}
