<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Response\TwoFactorToken\TransportMethodMessage;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A registered two-factor confirmation, and how long it stays valid.
 */
readonly class TwoFactorToken implements MessageInterface
{
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
     * @param ?\DateTimeInterface $operationDate
     * @param \DateTimeInterface|null $sentAt
     * @param ?\DateTimeInterface $expiresAt
     * @param TransportMethodMessage|null $transportMethod how the code was sent
     * @param TransportMethodMessage[] $availableMethods the ways the code can be sent, the current
     *        one included
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PHONE_NUMBER)]
        private string $phoneNumber,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_OPERATION_TITLE)]
        private string $operationTitle,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_OPERATION_NUMBER)]
        private int $operationNumber,

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_OPERATION_DATE)]
        private ?\DateTimeInterface $operationDate,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_SENT_AT)]
        private ?\DateTimeInterface $sentAt,

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_EXPIRES_AT)]
        private ?\DateTimeInterface $expiresAt,

        /**
         * @var TransportMethodMessage|null
         */
        #[Serializer\SerializedName(self::FIELD_TRANSPORT_METHOD)]
        private ?TransportMethodMessage $transportMethod = null,

        /**
         * @var TransportMethodMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_AVAILABLE_METHODS)]
        private array $availableMethods = [],
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string can be empty. To tell the customer where the code went, use the transport
     *         method's name instead, see {@see self::getTransportMethod()}
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * @return string
     */
    public function getOperationTitle(): string
    {
        return $this->operationTitle;
    }

    /**
     * @return int
     */
    public function getOperationNumber(): int
    {
        return $this->operationNumber;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getOperationDate(): ?\DateTimeInterface
    {
        return $this->operationDate;
    }

    /**
     * @return \DateTimeInterface|null null if it hasn't been sent
     */
    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getExpiresAt(): ?\DateTimeInterface
    {
        return  $this->expiresAt;
    }

    /**
     * How the code was sent. Its name says where, e.g. a masked phone number, so show that to tell
     * the customer where to look.
     *
     * @return TransportMethodMessage|null null if the token has no transport method
     */
    public function getTransportMethod(): ?TransportMethodMessage
    {
        return $this->transportMethod;
    }

    /**
     * The ways the code can be sent, the current one included. Pass one's id to
     * {@see \Polkurier\Webservice\Api\Service\TwoFactorTokenServiceInterface::twoFactorTokenChangeTransportMethod()}.
     *
     * @return list<TransportMethodMessage>
     */
    public function getAvailableMethods(): array
    {
        return \array_values($this->availableMethods);
    }
}
