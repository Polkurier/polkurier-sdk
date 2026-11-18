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
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * A legal document: the terms of service or the privacy policy.
 *
 * The content is an HTML fragment in Polish, meant to be embedded in a page.
 *
 * For the terms, pass the id to
 * {@see \Polkurier\Webservice\Api\Service\AccountServiceInterface::acceptRegulations()}. Only the
 * version currently in force can be accepted.
 *
 * @api
 */
final readonly class RegulationsMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_TITLE = 'title';
    public const string FIELD_CONTENT = 'content';
    public const string FIELD_DATE = 'date';
    /**-@#*/

    /**
     * @param string $id UUID of this version
     * @param string $title
     * @param string $content HTML fragment, in Polish
     * @param \DateTimeInterface|null $date when this version took effect
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id = '',

        #[Serializer\SerializedName(self::FIELD_TITLE)]
        public string $title = '',

        #[Serializer\SerializedName(self::FIELD_CONTENT)]
        public string $content = '',

        #[Serializer\SerializedName(self::FIELD_DATE)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM])]
        public ?\DateTimeInterface $date = null,

        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
