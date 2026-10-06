<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier\AdditionalData;

use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Whether a carrier offers cash on delivery, and whether it then requires insurance.
 */
readonly class Cod extends Property
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_REQUIRE_INSURANCE = 'require_insurance';
    /**-@#*/

    /**
     * @param bool $available
     * @param string $description
     * @param bool $requireInsurance
     */
    public function __construct(
        bool $available,
        string $description,
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRE_INSURANCE)]
        private bool $requireInsurance,
    ) {
        parent::__construct($available, $description);
    }

    /**
     * @return bool
     */
    public function isRequireInsurance(): bool
    {
        return $this->requireInsurance;
    }
}
