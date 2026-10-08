<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Geo;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Looks up the localities a postcode covers.
 */
#[Validate\PostcodeForCountry(postcode: 'postcode', country: 'country')]
readonly class FindCityMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_POSTCODE = 'postcode';
    public const string FIELD_COUNTRY = 'country';
    /**-@#*/

    public const string DEFAULT_COUNTRY = 'PL';

    /**
     * @param string $postcode
     * @param string $country ISO country code. Only Poland is supported, any other country
     *        returns an empty list (not an error)
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POSTCODE)]
        #[Validate\NotBlank]
        private string $postcode,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COUNTRY)]
        private string $country = self::DEFAULT_COUNTRY,
    ) {}

    /**
     * @return string
     */
    public function getPostcode(): string
    {
        return $this->postcode;
    }

    /**
     * ISO country code the postcode belongs to.
     *
     * Only Poland is supported. Any other country returns an empty list, not an error.
     *
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }
}
