<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Optional extras for the account profile.
 *
 * All 3 are off (or null) by default. Each one makes the call slower, so request only what you use.
 */
readonly class GetAccountInfoMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_EXTERNAL_APPLICATION_ID = 'external_application_id';
    /**-@#*/

    /**
     * @param ?string $externalApplicationId UUID of an external application. When set, the application's settings for this account are included
     */
    public function __construct(

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_EXTERNAL_APPLICATION_ID)]
        #[OmitWhenEmpty]
        private ?string $externalApplicationId = null,
    ) {}

    /**
     * @return ?string
     */
    public function getExternalApplicationId(): ?string
    {
        return $this->externalApplicationId;
    }
}
