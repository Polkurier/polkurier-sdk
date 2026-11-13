<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Account;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Removes a telephone number from the account.
 *
 * The id picks the number; `number` is only checked for format. The default number can't be
 * removed.
 *
 * Removing a number also withdraws any marketing consents given for it (calls and text messages).
 */
final readonly class DeletePhoneNumberMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NUMBER = 'number';
    /**-@#*/

    /**
     * @param string $id identifier from getPhoneNumbers()
     * @param string $number the telephone number, checked for format only
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[Validate\NotBlank]
        private string $id,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        #[Validate\NotBlank]
        #[Validate\PhoneNumber]
        private string $number,
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }
}
