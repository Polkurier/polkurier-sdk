<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Payment;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One movement on the prepaid balance.
 *
 * The amount is signed: negative for spending, positive for a top-up.
 */
readonly class PrepaidEntryMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_DATE = 'date';
    public const string FIELD_AMOUNT = 'amount';
    public const string FIELD_DESCRIPTION = 'description';
    /**-@#*/

    /**
     * @param ?\DateTimeInterface $date when the movement happened
     * @param float $amount signed amount in PLN
     * @param string $description what the movement was for, in Polish
     */
    public function __construct(
        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $date = null,

        /**
         * @var float
         */
        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        private float $amount = 0.0,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description = '',
    ) {}

    /**
     * @return ?\DateTimeInterface
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
