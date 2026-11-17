<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Http;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Dto\Response\Carrier;
use Polkurier\Webservice\Dto\Response\Courier\PickupTimeMessage;
use Polkurier\Webservice\Dto\Response\Invoice\InvoiceListMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxConfigMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutListMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderStatusMessage;
use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Polkurier\Webservice\Dto\Response\Payment\PrepaidHistoryMessage;
use Polkurier\Webservice\Dto\Response\System\CourierMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketListMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketMessage;
use Polkurier\Webservice\Dto\Response\TwoFactorToken;
use Polkurier\Webservice\Entity\Address;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Serializer\MessageSerializer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\MessageClasses;
use Polkurier\Webservice\Test\Support\Sample;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Responses whose shape varies with the request or the record still read.
 *
 * Each case starts from a fully populated response of the class, in its wire form, and changes it
 * the way the API can: a key left out, null or an empty string in place of a value, a whole number
 * where a decimal is expected, or keys the SDK doesn't know.
 */
final class ResponseShapeTest extends TestCase
{
    /**
     * @return array<string, array{string, \Closure(array<string, mixed>): mixed}>
     */
    public static function shapes(): array
    {
        $without = static fn(string ...$keys) => static fn(array $data): array => \array_diff_key(
            $data,
            \array_flip($keys),
        );
        $with = static fn(array $values) => static fn(array $data): array => $values + $data;
        $inList = static fn(\Closure $change) => static fn(array $data): array => [$change($data)];

        return [
            'an address without a point id or the cover address flag' => [
                Address::class . '[]',
                $inList($without('point_id', 'is_cover_address')),
            ],
            'an address with an empty country, province and flat number' => [
                Address::class . '[]',
                $inList($with(['country' => '', 'province' => '', 'flatnumber' => ''])),
            ],
            'a carrier listed without its additional data' => [
                Carrier::class . '[]',
                $inList($without('additional_data')),
            ],
            'a carrier without a points map' => [
                Carrier::class . '[]',
                $inList(static fn(array $c): array => [
                    'additional_data' => \array_diff_key((array)$c['additional_data'], ['points_map' => 0]),
                ] + $c),
            ],
            'a ticket list without a page count' => [
                TicketListMessage::class,
                $without('totalpages'),
            ],
            'a ticket without its messages or response template' => [
                TicketMessage::class,
                $without('messages', 'response_template'),
            ],
            'a ticket with no creation date and an empty status' => [
                TicketMessage::class,
                $with(['date_created' => null, 'status' => '']),
            ],
            'an invoice list with no paging' => [
                InvoiceListMessage::class,
                $with(['totalrows' => null, 'currentpage' => null, 'pagesize' => null]),
            ],
            'a prepaid history with no paging' => [
                PrepaidHistoryMessage::class,
                $with(['totalrows' => null, 'currentpage' => null, 'pagesize' => null]),
            ],
            'a payout list with no paging' => [
                MoneyBoxPayoutListMessage::class,
                $with(['totalrows' => null, 'currentpage' => null, 'pagesize' => null, 'totalpages' => null]),
            ],
            'a two-factor token not sent yet, without a phone number' => [
                TwoFactorToken::class,
                $with(['sent_at' => null, 'phone_number' => '']),
            ],
            'a scan event with no location or description' => [
                OrderStatusMessage::class,
                static fn(array $s): array => [
                    'tracking' => [['location' => null, 'description' => null] + (array)$s['tracking'][0]],
                ] + $s,
            ],
            'a pickup time dated without a time' => [
                PickupTimeMessage::class . '[]',
                $inList($with(['pickupdate' => '2026-10-06'])),
            ],
            'an invoice dated without a time' => [
                InvoiceListMessage::class,
                static fn(array $l): array => ['result' => [['date' => '2026-09-30'] + (array)$l['result'][0]]] + $l,
            ],
            'a carrier notice of the message kind' => [
                CourierMessage::class . '[]',
                $inList(
                    static fn(array $m): array => ['type' => 'MESSAGE'] + \array_diff_key($m, ['hide_timeout' => 0]),
                ),
            ],
            'a carrier notice of the alert kind' => [
                CourierMessage::class . '[]',
                $inList(static fn(array $m): array => ['type' => 'ALERT'] + \array_diff_key($m, [
                    'title' => 0,
                    'priority' => 0,
                ])),
            ],
            'account balances as whole numbers' => [
                AccountInfoMessage::class,
                $with(\array_fill_keys(self::BALANCES, 0)),
            ],
            'account balances with decimals' => [
                AccountInfoMessage::class,
                $with(\array_fill_keys(self::BALANCES, 12.34)),
            ],
            'an account without an invoice limit or payment account' => [
                AccountInfoMessage::class,
                $with([
                    'invoice_limit' => null,
                    'invoice_limit_used' => null,
                    'invoice_payment_account_number' => null,
                ]),
            ],
            'account settings and PIN locks the SDK doesn\'t know' => [
                AccountInfoMessage::class,
                static fn(array $a): array => [
                    'settings' => ['NEW_SETTING' => true] + (array)$a['settings'],
                    'pin_locks' => ['NEW_LOCK' => false] + (array)$a['pin_locks'],
                ] + $a,
            ],
            'a money box with no payout or fee type and an empty fee option' => [
                MoneyBoxConfigMessage::class,
                $with([
                    'payout_type' => null,
                    'fee_payment' => null,
                    'fee_payments_types_available' => ['INVOICE', ''],
                ]),
            ],
        ];
    }

    /**
     * Every paged list, found by scanning the response classes, so a new one is covered too.
     *
     * @return array<string, array{class-string}>
     */
    public static function pagedLists(): array
    {
        $lists = \array_filter(
            MessageClasses::responses(),
            static fn(string $class): bool => \is_subclass_of($class, PagedListMessage::class),
        );

        return \array_map(static fn(string $class): array => [$class], $lists);
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('pagedLists')]
    public function testEveryPagedListReadsNullPagingAsZero(string $class): void
    {
        $body = \json_encode([
            'status' => 'success',
            'response' => [
                'totalrows' => null,
                'currentpage' => null,
                'pagesize' => null,
                'totalpages' => null,
                'result' => [],
            ],
        ], \JSON_THROW_ON_ERROR);

        $list = (new ResponseFactory(new MessageSerializer(new SerializerProvider())))
            ->create(new Response(200, [], $body), $class)
            ->getEnvelope()
            ->response;

        self::assertInstanceOf(PagedListMessage::class, $list);
        self::assertSame(0, $list->totalRows);
        self::assertSame(0, $list->currentPage);
        self::assertSame(0, $list->pageSize);
    }

    /**
     * A lookup that finds nothing can answer with an empty array where a page is expected, e.g.
     * get_money_box_payouts for an unknown payout number. It reads as a page with no rows.
     *
     * @param class-string $class
     */
    #[DataProvider('pagedLists')]
    public function testEveryPagedListReadsAnEmptyPayloadAsAnEmptyPage(string $class): void
    {
        $list = (new ResponseFactory(new MessageSerializer(new SerializerProvider())))
            ->create(new Response(200, [], '{"status": "success", "response": []}'), $class)
            ->getEnvelope()
            ->response;

        self::assertInstanceOf($class, $list);
        self::assertSame(0, $list->totalRows);
        self::assertSame([], $list->result);
    }

    private const array BALANCES = [
        'money_box_balance',
        'prepaid_balance',
        'invoice_balance',
        'invoice_rebate',
        'bonuses_savings',
        'money_box_payout_available_balance',
        'money_box_to_payout_balance',
    ];

    /**
     * @param \Closure(array<string, mixed>): mixed $change
     */
    #[DataProvider('shapes')]
    public function testTheResponseReads(string $format, \Closure $change): void
    {
        $class = \str_ends_with($format, '[]') ? \substr($format, 0, -2) : $format;
        $serializer = (new SerializerProvider())->getSerializer();
        self::assertInstanceOf(NormalizerInterface::class, $serializer);
        /** @var class-string $class */
        $wire = $serializer->normalize(Sample::of($class), 'json');
        self::assertIsArray($wire);

        $body = \json_encode(
            ['status' => 'success', 'response' => $change($wire)],
            \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION,
        );
        $result = (new ResponseFactory(new MessageSerializer(new SerializerProvider())))
            ->create(new Response(200, [], $body), $format)
            ->getEnvelope()
            ->response;

        self::assertNotNull($result);
    }
}
