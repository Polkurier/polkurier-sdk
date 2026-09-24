<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Carrier\AdditionalData;

use Polkurier\Webservice\Api\MessageInterface;
use Symfony\Component\Serializer\Attribute as Serializer;

readonly class Option implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_STATUS = 'status';
    public const string FIELD_REQUIRED = 'required';
    public const string FIELD_DESCRIPTION = 'description';
    /**-@#*/

    /**
     * Every field has a default because a falsy value can be absent from the response rather than
     * present and false — `status` in particular. Absent therefore means false, not unknown, and
     * a carrier that does not offer an option may simply not describe it.
     *
     * @param bool $status
     * @param bool $required
     * @param string $description
     */
    public function __construct(
        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private bool $status = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_REQUIRED)]
        private bool $required = false,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description = '',
    ) {}

    /**
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
