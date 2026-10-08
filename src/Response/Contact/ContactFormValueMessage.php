<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;

/**
 * A named value on a contact form: one of the form's properties or starting values, or one of a
 * field's display properties.
 *
 * Their names vary per form, so they come as name and value pairs. A value that isn't a single
 * value, such as a list, is given as its JSON.
 */
readonly class ContactFormValueMessage implements MessageInterface
{
    /**
     * @param string $name
     * @param string|int|float|bool|null $value a list or other compound value as its JSON
     */
    public function __construct(
        /**
         * @var string
         */
        private string $name,

        /**
         * @var string|int|float|bool|null
         */
        private string|int|float|bool|null $value,
    ) {}

    /**
     * Turns values keyed by name into a list of them.
     *
     * @param array<array-key, mixed> $values
     *
     * @return list<self>
     */
    public static function listOf(array $values): array
    {
        $list = [];
        foreach ($values as $name => $value) {
            $list[] = new self(
                (string)$name,
                $value === null || \is_scalar($value) ? $value : (string)\json_encode($value, \JSON_UNESCAPED_UNICODE),
            );
        }

        return $list;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|int|float|bool|null a list or other compound value as its JSON
     */
    public function getValue(): string|int|float|bool|null
    {
        return $this->value;
    }
}
