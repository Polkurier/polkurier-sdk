<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\TwoFactorToken;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorNamespaceEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Starts a two-factor confirmation: which operation, and how to reach the user.
 */
readonly class RegisterMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NAMESPACE = 'namespace';
    /**-@#*/

    /**
     * @param TwoFactorNamespaceEnum $namespace the operation the token will authorise
     */
    public function __construct(
        /**
         * @var TwoFactorNamespaceEnum
         */
        #[Serializer\SerializedName(self::FIELD_NAMESPACE)]
        private TwoFactorNamespaceEnum $namespace,
    ) {}

    /**
     * @return TwoFactorNamespaceEnum
     */
    public function getNamespace(): TwoFactorNamespaceEnum
    {
        return $this->namespace;
    }
}
