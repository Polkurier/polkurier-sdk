<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Courier;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Courier\PointFunctionEnum;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * Finds pickup points.
 *
 * Three ways to search, and the shape of the reply follows: give an id to fetch one point and a
 * single object comes back; give a search phrase or nothing and a list comes back. At least one
 * carrier must always be named.
 *
 * Without a search phrase the result is paged; with one, the API returns the first matches and
 * ignores the paging parameters.
 */
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
     * @param int|null $limit
     * @param int|null $page
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_COURIERS)]
        #[Validate\NotEmptyList]
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
    ) {
        $this->validateCouriers();
        $this->validateFunctions();
    }

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

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateCouriers(): void
    {
        foreach ($this->couriers as $courier) {
            if (!is_string($courier)) {
                throw new InvalidArgumentException(\sprintf(
                    '"%s" field may only contain strings, %s provided.',
                    static::FIELD_COURIERS,
                    \gettype($courier)
                ));
            }
        }
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateFunctions(): void
    {
        foreach ($this->functions ?? [] as $function) {
            if (!($function instanceof PointFunctionEnum)) {
                throw new InvalidArgumentException(\sprintf(
                    '"%s" field may only contain instances of %s, %s provided.',
                    static::FIELD_FUNCTIONS,
                    PointFunctionEnum::class,
                    \gettype($function)
                ));
            }
        }
    }
}
