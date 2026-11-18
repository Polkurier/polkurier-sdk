<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Dto\Response\TwoFactorToken\TransportMethodMessage;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * A registered two-factor confirmation, and how long it stays valid.
 *
 * @api
 */
final readonly class TwoFactorToken implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_PHONE_NUMBER = 'phone_number';
    public const string FIELD_OPERATION_TITLE = 'operation_title';
    public const string FIELD_OPERATION_NUMBER = 'operation_number';
    public const string FIELD_OPERATION_DATE = 'operation_date';
    public const string FIELD_SENT_AT = 'sent_at';
    public const string FIELD_EXPIRES_AT = 'expires_at';
    public const string FIELD_TRANSPORT_METHOD = 'transport_method';
    public const string FIELD_AVAILABLE_METHODS = 'available_methods';
    /**-@#*/

    /**
     * @param string $id
     * @param string $phoneNumber
     * @param string $operationTitle
     * @param int $operationNumber
     * @param \DateTimeInterface|null $operationDate
     * @param \DateTimeInterface|null $sentAt
     * @param \DateTimeInterface|null $expiresAt
     * @param TransportMethodMessage|null $transportMethod how the code was sent
     * @param TransportMethodMessage[] $availableMethods the ways the code can be sent, the current
     *        one included
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[Sensitive]
        public string $id,

        /**
         * @var string can be empty. To tell the customer where the code went, use the transport method's name instead,
         *         see {@see self::$transportMethod}
         */
        #[Serializer\SerializedName(self::FIELD_PHONE_NUMBER)]
        #[PersonalData]
        public string $phoneNumber,

        #[Serializer\SerializedName(self::FIELD_OPERATION_TITLE)]
        public string $operationTitle,

        #[Serializer\SerializedName(self::FIELD_OPERATION_NUMBER)]
        public int $operationNumber,

        #[Serializer\SerializedName(self::FIELD_OPERATION_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $operationDate,

        /**
         * @var \DateTimeInterface|null null if it hasn't been sent
         */
        #[Serializer\SerializedName(self::FIELD_SENT_AT)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $sentAt,

        #[Serializer\SerializedName(self::FIELD_EXPIRES_AT)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $expiresAt,

        /**
         * How the code was sent. Its name says where, e.g. a masked phone number, so show that to tell
         * the customer where to look.
         *
         * @var TransportMethodMessage|null null if the token has no transport method
         */
        #[Serializer\SerializedName(self::FIELD_TRANSPORT_METHOD)]
        public ?TransportMethodMessage $transportMethod = null,

        /**
         * The ways the code can be sent, the current one included. Pass one's id to
         * {@see \Polkurier\Webservice\Api\Service\TwoFactorTokenServiceInterface::changeTransportMethod()}.
         *
         * @var TransportMethodMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE_METHODS)]
        public array $availableMethods = [],

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
