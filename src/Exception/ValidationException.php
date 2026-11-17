<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

use Polkurier\Webservice\Validator\Violation;

/**
 * A payload was rejected before it was sent.
 *
 * Holds the failures per field, in the same shape the API uses when it rejects a payload, so the
 * same code can display both. The difference is where it came from. This exception means nothing
 * was sent and nothing changed. A {@see \Polkurier\Webservice\Exception\WebserviceException} with
 * validation errors means the request was sent and refused.
 *
 * Field names are the API's wire names, not SDK property names, so they match the API's own
 * reports and the request that would have been sent. A field inside a nested record is named
 * under its parent, `sender.postcode`; an entry of a list carries its index, `packs[1].weight`;
 * and a record the payload inlines reports at the top level, so an order's fields in
 * `create_order` have no prefix. The format is part of the contract: a release keeps it, so code
 * that maps these keys to its own form fields goes on working.
 *
 * @api
 */
class ValidationException extends InvalidArgumentException
{
    /**
     * @param array<string, string[]> $validationErrors failures keyed by wire field path
     * @param string $message
     * @param list<Violation> $violations the same failures, each with its code and parameters
     */
    public function __construct(
        /**
         * @var array<string, string[]>
         */
        private readonly array $validationErrors,
        string $message = '',

        /**
         * @var list<Violation>
         */
        private readonly array $violations = [],
    ) {
        parent::__construct($message !== '' ? $message : self::summarise($validationErrors));
    }

    /**
     * Every failure, each with the code to match on and the values its sentence spells out.
     *
     * The English in {@see self::getValidationErrors()} is worded to follow the field name and
     * names values inside itself, so it cannot be matched on. These carry
     * {@see Violation::$code}, which does not change, for an integration that words its own
     * report. Empty when the exception was built from the English alone.
     *
     * @return list<Violation>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * All failures, keyed by wire field path.
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
