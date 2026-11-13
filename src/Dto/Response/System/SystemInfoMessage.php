<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\System;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Limits and policy to validate input against.
 *
 * These values can change without an API version bump.
 *
 * @api
 */
final readonly class SystemInfoMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_VERIFICATION_TRANSFER_AMOUNT_PLN = 'verification_transfer_amount_pln';
    public const string FIELD_REGULATIONS_ACCEPTANCE_PERIOD = 'regulations_acceptance_period';
    public const string FIELD_COUPONS = 'coupons';
    public const string FIELD_TICKETS = 'tickets';
    public const string FIELD_SECURITY = 'security';
    public const string FIELD_FEATURES = 'features';
    /**-@#*/

    /**
     * @param float $verificationTransferAmountPln amount, in PLN, of the token transfer used to
     *                                           verify a bank account
     * @param int $regulationsAcceptancePeriod days a customer has to accept revised terms before
     *        {@see \Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage::$regulationsAccepted}
     *        turns false
     * @param CouponLimitsMessage|null $coupons
     * @param TicketLimitsMessage|null $tickets
     * @param SecurityLimitsMessage|null $security
     * @param array<string, bool> $features feature flags the service reports, keyed by name
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_VERIFICATION_TRANSFER_AMOUNT_PLN)]
        public float $verificationTransferAmountPln = 0.0,

        /**
         * @var int
         */
        #[Serializer\SerializedName(self::FIELD_REGULATIONS_ACCEPTANCE_PERIOD)]
        public int $regulationsAcceptancePeriod = 0,

        /**
         * @var CouponLimitsMessage|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_COUPONS)]
        public ?CouponLimitsMessage $coupons = null,

        /**
         * @var TicketLimitsMessage|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_TICKETS)]
        public ?TicketLimitsMessage $tickets = null,

        /**
         * @var SecurityLimitsMessage|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_SECURITY)]
        public ?SecurityLimitsMessage $security = null,

        /**
         * Feature flags the service reports, keyed by name.
         *
         * @var array<string, bool> empty if the response leaves them out
         */
        #[Serializer\SerializedName(self::FIELD_FEATURES)]
        public array $features = [],

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
