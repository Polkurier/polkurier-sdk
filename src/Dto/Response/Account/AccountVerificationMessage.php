<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Account\AccountVerificationStatusEnum;
use Polkurier\Webservice\Enum\Account\AccountVerificationTypeEnum;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * An identity verification attempt and where it has got to.
 *
 * The access token, for the verification provider's flow, comes only from registering. It is valid
 * for up to three hours: calls within each three-hour period counted from the start of the
 * verification return the same token, and a call in the next period returns a new one.
 *
 * @api
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
     * @param AccountVerificationTypeEnum|null $type how the account is being verified
     * @param AccountVerificationStatusEnum|null $status where the attempt has got to
     * @param string|null $accessToken returned only when registering, null when reading back
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_VERIFICATION_ID)]
        public string $verificationId = '',

        /**
         * @var AccountVerificationTypeEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        public ?AccountVerificationTypeEnum $type = null,

        /**
         * @var AccountVerificationStatusEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public ?AccountVerificationStatusEnum $status = null,

        #[Serializer\SerializedName(self::FIELD_ACCESS_TOKEN)]
        #[Sensitive]
        public ?string $accessToken = null,

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
