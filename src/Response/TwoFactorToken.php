<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response;

use Polkurier\Webservice\Api\MessageInterface;
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
    /**-@#*/

    /**
     * @param string $id
     * @param string $phoneNumber
     * @param string $operationTitle
     * @param int $operationNumber
     * @param \DateTimeInterface $operationDate
     * @param \DateTimeInterface $sentAt
     * @param \DateTimeInterface $expiresAt
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
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_OPERATION_DATE)]
        private \DateTimeInterface $operationDate,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_SENT_AT)]
        private \DateTimeInterface $sentAt,

        /**
         * @var \DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_EXPIRES_AT)]
        private \DateTimeInterface $expiresAt,
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
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
     * @return \DateTimeInterface
     */
    public function getOperationDate(): \DateTimeInterface
    {
        return $this->operationDate;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getSentAt(): \DateTimeInterface
    {
        return $this->sentAt;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getExpiresAt(): \DateTimeInterface
    {
        return  $this->expiresAt;
    }
}
