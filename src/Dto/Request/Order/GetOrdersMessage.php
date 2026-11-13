<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Order;

use Polkurier\Webservice\Dto\Request\Common\PagedMessage;
use Polkurier\Webservice\Enum\Order\OrderStatusCodeEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Polkurier\Webservice\Validator\Rule as Validate;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * Filters and paging for the order list.
 *
 * Every field is optional. With none set, you get the first page, unfiltered. The parcel, item and
 * file flags each make the response much larger, so leave them off unless you need them.
 */
#[Validate\InOrder(earlier: 'dateFrom', later: 'dateTo')]
#[Validate\InOrder(earlier: 'codMin', later: 'codMax')]
#[Validate\PageSizeAtMost(self::MAX_PAGE_SIZE)]
#[Validate\RequiredWith(
    field: 'externalApplicationId',
    with: 'externalOrderId',
    because: 'is required to filter by external order id',
)]
final readonly class GetOrdersMessage extends PagedMessage
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ORDER_NO = 'orderno';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_COURIER = 'courier';
    public const string FIELD_COD_MIN = 'cod_min';
    public const string FIELD_COD_MAX = 'cod_max';
    public const string FIELD_DATE_FROM = 'date_from';
    public const string FIELD_DATE_TO = 'date_to';
    public const string FIELD_SEARCH_PHRASE = 'search';
    public const string FIELD_EXTERNAL_APPLICATION_ID = 'external_application_id';
    public const string FIELD_EXTERNAL_ORDER_ID = 'external_order_id';
    public const string FIELD_PACKS = 'packs';
    public const string FIELD_ITEMS = 'items';
    public const string FIELD_FILES = 'files';
    /**-@#*/

    public const int MAX_PAGE_SIZE = 100;

    /**
     * @param string|null $orderNo one order, by its number, with its parcels, items and files. The
     *        other filters are ignored, and the result is still a page
     * @param OrderStatusCodeEnum|null $status orders in this delivery state
     * @param string|null $courier orders with this carrier, by its service code
     * @param float|null $codMin smallest cash-on-delivery amount, in PLN
     * @param float|null $codMax largest cash-on-delivery amount, in PLN
     * @param \DateTimeInterface|null $dateFrom earliest creation date
     * @param \DateTimeInterface|null $dateTo latest creation date
     * @param string|null $searchPhrase free text, matched against order numbers, waybills, your
     *        external order ids and the recipient's name, company, address, phone, email and point
     * @param int|null $pageSize 1 to {@see self::MAX_PAGE_SIZE}
     * @param int|null $page starts at 1
     * @param string|null $externalApplicationId lists only orders placed with this application id,
     *        exact match
     * @param string|null $externalOrderId lists only the order with this id of yours, exact match.
     *        Needs $externalApplicationId as well, and the SDK refuses it without one
     * @param bool|null $packs include each order's parcels
     * @param bool|null $items include each order's priced items
     * @param bool|null $files include each order's attached files
     */
    public function __construct(
        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_ORDER_NO)]
        #[OmitWhenEmpty]
        public ?string $orderNo = null,

        /**
         * @var OrderStatusCodeEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        #[OmitWhenEmpty]
        public ?OrderStatusCodeEnum $status = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_COURIER)]
        #[OmitWhenEmpty]
        public ?string $courier = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_COD_MIN)]
        #[OmitWhenEmpty]
        public ?float $codMin = null,

        /**
         * @var float|null
         */
        #[Serializer\SerializedName(self::FIELD_COD_MAX)]
        #[OmitWhenEmpty]
        public ?float $codMax = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DATE_FROM)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[OmitWhenEmpty]
        public ?\DateTimeInterface $dateFrom = null,

        /**
         * @var \DateTimeInterface|null
         */
        #[Serializer\SerializedName(self::FIELD_DATE_TO)]
        #[Serializer\Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[OmitWhenEmpty]
        public ?\DateTimeInterface $dateTo = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_SEARCH_PHRASE)]
        #[OmitWhenEmpty]
        public ?string $searchPhrase = null,

        ?int $pageSize = null,

        ?int $page = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_EXTERNAL_APPLICATION_ID)]
        #[OmitWhenEmpty]
        public ?string $externalApplicationId = null,

        /**
         * @var string|null
         */
        #[Serializer\SerializedName(self::FIELD_EXTERNAL_ORDER_ID)]
        #[OmitWhenEmpty]
        public ?string $externalOrderId = null,

        /**
         * @var bool|null
         */
        #[Serializer\SerializedName(self::FIELD_PACKS)]
        #[OmitWhenEmpty]
        public ?bool $packs = null,

        /**
         * @var bool|null
         */
        #[Serializer\SerializedName(self::FIELD_ITEMS)]
        #[OmitWhenEmpty]
        public ?bool $items = null,

        /**
         * @var bool|null
         */
        #[Serializer\SerializedName(self::FIELD_FILES)]
        #[OmitWhenEmpty]
        public ?bool $files = null,
    ) {
        parent::__construct(pageSize: $pageSize, page: $page);
    }
}
