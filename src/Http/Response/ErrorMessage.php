<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Http\Response;

use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
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
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Field-level validation messages, keyed by the input field they concern.
     *
     * Present only on VALIDATION_ERROR responses, and the single most useful part of one: the main
     * message names just the first failing field, while this carries every failure at once. The
     * keys are the API's own field names, so they map onto what was sent rather than onto SDK
     * property names.
     *
     * @return array<string, string[]>|null
     */
    public function getValidationErrors(): ?array
    {
        return $this->validationErrors;
    }
}
