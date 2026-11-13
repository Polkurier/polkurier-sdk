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
 * Records the customer's acceptance of the current terms.
 *
 * The id must match the version currently in force. Read it from
 * {@see \Polkurier\Webservice\Api\Service\AccountServiceInterface::getRegulations()} just before
 * accepting.
 */
final readonly class AcceptRegulationsMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_REGULATIONS_ID = 'regulations_id';
    /**-@#*/

    /**
     * @param string $regulationsId UUID of the terms version being accepted
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_REGULATIONS_ID)]
        #[Validate\NotBlank]
        public string $regulationsId,
    ) {}
}
