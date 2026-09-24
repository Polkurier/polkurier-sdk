<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Narrows a carrier listing.
 *
 * Requesting the additional data makes the response considerably larger — it carries every
 * service, pack type and pickup rule per carrier — so ask for it when building a shipping form,
 * not on every page load.
 */
readonly class AvailableCarriersMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ADDITIONAL_DATA = 'additional_data';
    public const string FIELD_RETURN_CARRIER = 'returncarrier';
    /**-@#*/

    /**
     * @param bool|null $additionalData
     * @param string|null $returnCarrier
     */
    public function __construct(
        /**
         * @var bool|null
         */
        #[Serializer\SerializedName(self::FIELD_ADDITIONAL_DATA)]
        #[OmitWhenEmpty]
        private ?bool $additionalData = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_RETURN_CARRIER)]
        #[OmitWhenEmpty]
        private ?string $returnCarrier = null,
    ) {}

    /**
     * @return bool|null
     */
    public function isAdditionalData(): ?bool
    {
        return $this->additionalData;
    }

    /**
     * @return string|null
     */
    public function getReturnCarrier(): ?string
    {
        return $this->returnCarrier;
    }
}
