<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An error response: the message, and the codes and field errors that classify it.
 *
 * @property string $response
 * @method string getResponse():
 */
final readonly class ErrorMessage extends ResponseMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_STATUS_CODE = 'status_code';
    public const string FIELD_ERROR_CODE = 'error_code';
    public const string FIELD_VALIDATION_ERRORS = 'validation_errors';
    /**-@#*/

    /**
     * @param ResponseStatusEnum $status
     * @param string $response
     * @param int|null $statusCode
     * @param string|null $errorCode
     * @param array<string, string[]>|null $validationErrors
     */
    public function __construct(
        ResponseStatusEnum $status,
        string $response,

        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_CODE)]
        #[OmitWhenEmpty]
        private ?int $statusCode = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ERROR_CODE)]
        #[OmitWhenEmpty]
        private ?string $errorCode = null,

        /**
         * @var array<string, string[]>|null
         */
        #[Serializer\SerializedName(self::FIELD_VALIDATION_ERRORS)]
        #[OmitWhenEmpty]
        private ?array $validationErrors = null,
    ) {
        parent::__construct($status, $response);
    }

    /**
     * @return int|null null when the error doesn't carry one. Either code can be missing
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return string|null null when the error doesn't carry one. Either code can be missing
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Field-level validation messages, keyed by input field.
     *
     * Only present on VALIDATION_ERROR responses. The main message names only the first failing
     * field, while this holds every failure. The keys are the API's field names as sent, not SDK
     * property names.
     *
     * @return array<string, string[]>|null
     */
    public function getValidationErrors(): ?array
    {
        return $this->validationErrors;
    }
}
