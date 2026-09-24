<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Exception;

/**
 * A payload was rejected before it was sent.
 *
 * Carries the failures per field, in the same shape the API uses when it rejects a payload itself,
 * so the same code can present both. The distinction that matters to a caller is where it came
 * from: this one means nothing was sent and nothing changed, whereas a
 * {@see WebserviceException} carrying validation errors means the request was made and refused.
 *
 * Field names are the ones the API uses on the wire, not SDK property names, so they line up with
 * the API's own reports and with the request that would have been sent.
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
     * Every failure, keyed by the wire field name it concerns.
     *
     * @return array<string, string[]>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * The failures for one field, or an empty list when it has none.
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
