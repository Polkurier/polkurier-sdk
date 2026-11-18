<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Account;

use Polkurier\Webservice\Dto\Request\Common\IdMessage;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Removes a phone number from the account.
 *
 * The id picks the number; `number` is only checked for format. The default number can't be
 * removed.
 *
 * Removing a number also withdraws any marketing consents given for it (calls and text messages).
 *
 * @api
 */
final readonly class DeletePhoneNumberMessage extends IdMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_NUMBER = 'number';
    /**-@#*/

    /**
     * @param string $id identifier from getPhoneNumbers()
     * @param string $number the phone number, checked for format only
     */
    public function __construct(
        string $id,

        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        #[Validate\NotBlank]
        #[Validate\PhoneNumber]
        #[PersonalData]
        public string $number,
    ) {
        parent::__construct($id);
    }
}
