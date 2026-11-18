<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Payment;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * One movement on the prepaid balance.
 *
 * The amount is signed: negative for spending, positive for a top-up.
 *
 * @api
 */
final readonly class PrepaidEntryMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_DATE = 'date';
    public const string FIELD_AMOUNT = 'amount';
    public const string FIELD_DESCRIPTION = 'description';
    /**-@#*/

    /**
     * @param \DateTimeInterface|null $date when the movement happened
     * @param float $amount signed amount in PLN
     * @param string $description what the movement was for, in Polish: "Doładowanie konta" for a
     *        positive amount, "Pobranie środków z konta" otherwise, sometimes followed by a note
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $date = null,

        #[Serializer\SerializedName(self::FIELD_AMOUNT)]
        public float $amount = 0.0,

        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description = '',
    ) {}
}
