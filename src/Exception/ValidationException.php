<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

/**
 * A payload was rejected before it was sent.
 *
 * Holds the failures per field, in the same shape the API uses when it rejects a payload, so the
 * same code can display both. The difference is where it came from. This exception means nothing
 * was sent and nothing changed. A {@see \Polkurier\Webservice\Exception\WebserviceException} with
 * validation errors means the request was sent and refused.
 *
 * Field names are the API's wire names, not SDK property names, so they match the API's own
 * reports and the request that would have been sent.
 *
 * @api
 */
class ValidationException extends InvalidArgumentException
{
    /**
     * @param array<string, string[]> $validationErrors failures keyed by wire field name
     * @param string $message
     */
    public function __construct(
        /**
         * @var array<string, string[]>
         */
        private readonly array $validationErrors,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : self::summarise($validationErrors));
    }

    /**
     * All failures, keyed by wire field name.
     *
     * @return array<string, string[]>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * The failures for one field, or an empty list if there are none.
     *
     * @param string $field
     *
     * @return string[]
     */
    public function getFieldErrors(string $field): array
    {
        return $this->validationErrors[$field] ?? [];
    }

    /**
     * @param array<string, string[]> $validationErrors
     *
     * @return string
     */
    private static function summarise(array $validationErrors): string
    {
        $parts = [];
        foreach ($validationErrors as $field => $messages) {
            foreach ($messages as $singleMessage) {
                $parts[] = \sprintf('%s: %s', $field, $singleMessage);
            }
        }

        return $parts === []
            ? 'The payload is invalid.'
            : \sprintf('The payload is invalid. %s', \implode('; ', $parts));
    }
}
