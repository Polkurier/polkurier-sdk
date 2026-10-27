<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Finds pickup points.
 *
 * With an id, you get a single point object. With a search phrase, or with neither, you get a
 * list. At least one carrier is always required.
 *
 * Without a search phrase the list is paged. With one, you get the first matches and paging does
 * not apply.
 *
 * `page` requires `limit`: without it the whole set comes back.
 */
#[Validate\RequiredWith(field: 'limit', with: 'page')]
readonly class GetPointMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_COURIERS = 'couriers';
    public const string FIELD_ID = 'id';
    public const string FIELD_SEARCH_QUERY = 'searchquery';
    public const string FIELD_FUNCTIONS = 'functions';
    public const string FIELD_LIMIT = 'limit';
    public const string FIELD_PAGE = 'page';
    /**-@#*/

    /**
     * @param string[] $couriers
     * @param string|null $id
     * @param string|null $searchQuery
     * @param PointFunctionEnum[]|null $functions
     * @param int|null $limit points per page; without it the whole filtered set comes back
     * @param int|null $page 1-based page number; requires $limit
     */
    public function __construct(
        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_COURIERS)]
        #[Validate\NotEmptyList]
        #[Validate\Each(Validate\OfType::class, 'string')]
        private array $couriers,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        #[OmitWhenEmpty]
        private ?string $id = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SEARCH_QUERY)]
        #[OmitWhenEmpty]
        private ?string $searchQuery = null,

        /**
         * @var PointFunctionEnum[]|null
         */
        #[Serializer\SerializedName(self::FIELD_FUNCTIONS)]
        #[OmitWhenEmpty]
        #[Validate\Each(Validate\OfType::class, PointFunctionEnum::class)]
        private ?array $functions = null,

        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_LIMIT)]
        #[Validate\IntegerRange(1, \PHP_INT_MAX)]
        #[OmitWhenEmpty]
        private ?int $limit = null,

        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_PAGE)]
        #[Validate\IntegerRange(1, \PHP_INT_MAX)]
        #[OmitWhenEmpty]
        private ?int $page = null,
    ) {}

    /**
     * @return string[]
     */
    public function getCouriers(): array
    {
        return $this->couriers;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getSearchQuery(): ?string
    {
        return $this->searchQuery;
    }

    /**
     * @return PointFunctionEnum[]|null
     */
    public function getFunctions(): ?array
    {
        return $this->functions;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return int|null
     */
    public function getPage(): ?int
    {
        return $this->page;
    }
}
