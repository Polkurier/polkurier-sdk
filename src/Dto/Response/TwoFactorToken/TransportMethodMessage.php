<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\TwoFactorToken;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\TwoFactorToken\TwoFactorTransportTypeEnum;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A way a two-factor code can reach the customer: a phone number, an email address or an
 * authenticator app.
 *
 * Pass the id to
 * {@see \Polkurier\Webservice\Api\Service\TwoFactorTokenServiceInterface::twoFactorTokenChangeTransportMethod()}
 * to switch a pending confirmation to this method.
 */
final readonly class TransportMethodMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NAME = 'name';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_TYPE_NAME = 'type_name';
    /**-@#*/

    /**
     * @param string $id
     * @param string $name where the code goes, with email addresses and phone numbers partly masked
     * @param TwoFactorTransportTypeEnum|null $type
     * @param string $description a sentence describing the method, in Polish
     * @param string $typeName the method's display name, in Polish
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name = '',

        /**
         * @var TwoFactorTransportTypeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        private ?TwoFactorTransportTypeEnum $type = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_TYPE_NAME)]
        private string $typeName = '',
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Where the code goes, e.g. a phone number or email address with part of it masked with `***`,
     * or the customer's label for an authenticator app. Show this to tell the customer where to
     * look.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return TwoFactorTransportTypeEnum|null null for a value this version of the SDK doesn't know
     */
    public function getType(): ?TwoFactorTransportTypeEnum
    {
        return $this->type;
    }

    /**
     * @return string a sentence describing the method, in Polish
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * The method's display name, in Polish. For labels in your own language, switch on
     * {@see self::getType()} instead.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return $this->typeName;
    }
}
