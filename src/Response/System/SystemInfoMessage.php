<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Limits and policy to validate input against.
 *
 * The numbers are policy and can change without an API version bump.
 */
readonly class SystemInfoMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_VERIFICATION_TRANSFER_AMOUNT_PLN = 'verification_transfer_amount_pln';
    public const string FIELD_REGULATIONS_ACCEPTANCE_PERIOD = 'regulations_acceptance_period';
    public const string FIELD_COUPONS = 'coupons';
    public const string FIELD_TICKETS = 'tickets';
    public const string FIELD_SECURITY = 'security';
    /**-@#*/

    /**
     * @param int $verificationTransferAmountPln amount, in PLN, of the token transfer used to
     *                                           verify a bank account
     * @param int $regulationsAcceptancePeriod days a customer has to accept revised terms before
     *                                         the account is restricted
     * @param CouponLimitsMessage|null $coupons
     * @param TicketLimitsMessage|null $tickets
     * @param SecurityLimitsMessage|null $security
     */
    public function __construct(
        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_VERIFICATION_TRANSFER_AMOUNT_PLN)]
        private int $verificationTransferAmountPln = 0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_REGULATIONS_ACCEPTANCE_PERIOD)]
        private int $regulationsAcceptancePeriod = 0,

        /**
         * @var CouponLimitsMessage|null
         */
        #[Serializer\SerializedName(self::FIELD_COUPONS)]
        private ?CouponLimitsMessage $coupons = null,

        /**
         * @var TicketLimitsMessage|null
         */
        #[Serializer\SerializedName(self::FIELD_TICKETS)]
        private ?TicketLimitsMessage $tickets = null,

        /**
         * @var SecurityLimitsMessage|null
         */
        #[Serializer\SerializedName(self::FIELD_SECURITY)]
        private ?SecurityLimitsMessage $security = null,
    ) {}

    /**
     * @return int
     */
    public function getVerificationTransferAmountPln(): int
    {
        return $this->verificationTransferAmountPln;
    }

    /**
     * @return int
     */
    public function getRegulationsAcceptancePeriod(): int
    {
        return $this->regulationsAcceptancePeriod;
    }

    /**
     * @return CouponLimitsMessage|null null if the response leaves it out
     */
    public function getCoupons(): ?CouponLimitsMessage
    {
        return $this->coupons;
    }

    /**
     * @return TicketLimitsMessage|null null if the response leaves it out
     */
    public function getTickets(): ?TicketLimitsMessage
    {
        return $this->tickets;
    }

    /**
     * @return SecurityLimitsMessage|null null if the response leaves it out
     */
    public function getSecurity(): ?SecurityLimitsMessage
    {
        return $this->security;
    }
}
