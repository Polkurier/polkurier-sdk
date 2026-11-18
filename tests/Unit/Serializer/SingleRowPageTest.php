<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutListMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderListMessage;
use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketListMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\SingleRowPageDenormalizer;

/**
 * Filtered to one record, some lists answer with the record itself instead of a page.
 *
 * `get_orders` by order number, `get_tickets` by id and `get_money_box_payouts` by payout number
 * all do it. Read as a page such a body has none of a page's fields, so it would arrive empty and
 * the call would look like it found nothing. It is wrapped in a page of one row instead.
 */
#[CoversClass(SingleRowPageDenormalizer::class)]
final class SingleRowPageTest extends TestCase
{
    /**
     * @param class-string<PagedListMessage> $page
     * @param array<string, mixed> $record the record as the API sends it, alone
     * @param class-string $row
     *
     * @return void
     */
    #[DataProvider('singleRecords')]
    public function testARecordSentAloneReadsAsAPageOfOneRow(string $page, array $record, string $row): void
    {
        $read = (new SerializerProvider())->getSerializer()->denormalize($record, $page, 'json');

        self::assertInstanceOf($page, $read);
        self::assertSame(1, $read->totalRows);
        self::assertSame(1, $read->currentPage);
        self::assertSame(1, $read->pageSize);
        // Each page declares its own `result`, typed to the rows it carries, so the base this
        // test is written against does not have the property.
        $rows = \get_object_vars($read)['result'] ?? null;
        self::assertIsArray($rows);
        self::assertCount(1, $rows);
        self::assertInstanceOf($row, $rows[0]);
    }

    /**
     * A page is read as it arrived, so an empty page stays empty rather than becoming a row.
     *
     * @param class-string<PagedListMessage> $page
     * @param array<string, mixed> $record
     * @param class-string $row
     *
     * @return void
     */
    #[DataProvider('singleRecords')]
    public function testAPageIsLeftAsItIs(string $page, array $record, string $row): void
    {
        $wire = [
            PagedListMessage::FIELD_TOTAL_ROWS => 0,
            PagedListMessage::FIELD_CURRENT_PAGE => 1,
            PagedListMessage::FIELD_PAGE_SIZE => 100,
            PagedListMessage::FIELD_RESULT => [],
        ];

        $read = (new SerializerProvider())->getSerializer()->denormalize($wire, $page, 'json');

        self::assertInstanceOf($page, $read);
        self::assertSame(0, $read->totalRows);
        self::assertSame(100, $read->pageSize);
        self::assertSame([], \get_object_vars($read)['result'] ?? null);
    }

    /**
     * @return array<string, array{class-string<PagedListMessage>, array<string, mixed>, class-string}>
     */
    public static function singleRecords(): array
    {
        return [
            'an order read by its number' => [
                OrderListMessage::class,
                [Order::FIELD_NUMBER => '20534-7674', Order::FIELD_COURIER => 'DPD'],
                Order::class,
            ],
            'a ticket read by its id' => [
                TicketListMessage::class,
                [TicketMessage::FIELD_NUMBER => 'T-1001'],
                TicketMessage::class,
            ],
            'a payout read by its number' => [
                MoneyBoxPayoutListMessage::class,
                [MoneyBoxPayoutMessage::FIELD_NUMBER => 'P-55'],
                MoneyBoxPayoutMessage::class,
            ],
        ];
    }
}
