<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Geo;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Looks up the localities a postcode covers.
 *
 * @api
 */
#[Validate\PostcodeForCountry(postcode: 'postcode', country: 'country', whenEmpty: self::DEFAULT_COUNTRY)]
final readonly class FindCityMessage implements MessageInterface
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
     * @param string $country ISO country code. Any country other than PL gives an empty list
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_POSTCODE)]
        #[Validate\NotBlank]
        public string $postcode,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_COUNTRY)]
        public string $country = self::DEFAULT_COUNTRY,
    ) {}
}
