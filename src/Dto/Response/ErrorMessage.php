<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Http\ResponseStatusEnum;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * The envelope of an error response: the message, and the codes and field errors that classify it.
 *
 * @api
 */
final readonly class ErrorMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_STATUS = 'status';
    public const string FIELD_RESPONSE = 'response';
    public const string FIELD_STATUS_CODE = 'status_code';
    public const string FIELD_ERROR_CODE = 'error_code';
    public const string FIELD_VALIDATION_ERRORS = 'validation_errors';
    public const string FIELD_NONCE = 'nonce';
    /**-@#*/

    /**
     * @param ResponseStatusEnum $status
     * @param string $response
     * @param int|null $statusCode
     * @param string|null $errorCode
     * @param array<string, string[]>|null $validationErrors
     * @param string|null $nonce
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public ResponseStatusEnum $status,

        /**
         * The API's message.
         */
        #[Serializer\SerializedName(self::FIELD_RESPONSE)]
        public string $response,

        /**
         * @var int|null null when the error doesn't carry one. Either code can be missing
         */
        #[Serializer\SerializedName(self::FIELD_STATUS_CODE)]
        public ?int $statusCode = null,

        /**
         * @var string|null null when the error doesn't carry one. Either code can be missing
         */
        #[Serializer\SerializedName(self::FIELD_ERROR_CODE)]
        public ?string $errorCode = null,

        /**
         * Field-level validation messages, keyed by input field.
         *
         * Only present on VALIDATION_ERROR responses. The main message names only the first failing
         * field, while this holds every failure. The keys are the API's field names as sent, not SDK
         * property names.
         *
         * @var array<string, string[]>|null
         */
        #[Serializer\SerializedName(self::FIELD_VALIDATION_ERRORS)]
        public ?array $validationErrors = null,

        /**
         * The nonce for the next submission of a rejected contact form.
         *
         * @var string|null null for any other error
         */
        #[Serializer\SerializedName(self::FIELD_NONCE)]
        #[Sensitive]
        public ?string $nonce = null,
    ) {}
}
