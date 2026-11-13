<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An identity verification attempt and where it has got to.
 *
 * The access token, for the verification provider's flow, comes only from registering. It is valid
 * for up to three hours: calls within each three-hour period counted from the start of the
 * verification return the same token, and a call in the next period returns a new one.
 */
final readonly class AccountVerificationMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_VERIFICATION_ID = 'verification_id';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_ACCESS_TOKEN = 'access_token';
    /**-@#*/

    /**
     * @param string $verificationId
     * @param string $type how the account is being verified: "online" (an online payment) or
     *        "bank_transfer"
     * @param string $status where the attempt has got to: NEW, PAYMENT_PLACED, PAYMENT_RECEIVED,
     *        INCONCLUSIVE, VERIFIED or CANCELLED
     * @param string|null $accessToken returned only when registering, null when reading back
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_VERIFICATION_ID)]
        public string $verificationId = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public string $type = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public string $status = '',

        /**
         * @var ?string
         */
        #[Serializer\SerializedName(self::FIELD_ACCESS_TOKEN)]
        #[OmitWhenEmpty]
        public ?string $accessToken = null,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
