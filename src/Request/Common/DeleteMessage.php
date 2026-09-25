<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Common;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Identifies a record to delete by its id.
 *
 * Address-book entries are identified by their role too, so use
 * {@see \Polkurier\Webservice\Request\Address\DeleteMessage} for those.
 */
readonly class DeleteMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    /**-@#*/

    /**
     * @param string $id
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[Validate\NotBlank]
        private string $id,
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
