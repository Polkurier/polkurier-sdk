<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Response\Order;
use Polkurier\Webservice\Response\Order\OrderListMessage;
use Polkurier\Webservice\Response\PagedListMessage;
use Polkurier\Webservice\Response\Ticket\TicketListMessage;
use Polkurier\Webservice\Response\Ticket\TicketMessage;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Reads a single record as a one-row page where a page of records is expected.
 *
 * Filtered down to one record, `get_orders` (by order number) and `get_tickets` (by id) answer
 * with the record itself instead of a page. Read as a page, its fields would be ignored and the
 * record would look like an empty page. This wraps it in a page with that one row, so the result
 * has the same shape either way.
 */
class SingleRowPageDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    /**
     * For each page class, a key only a single record has, which tells it from a page.
     *
     * @var array<class-string<PagedListMessage>, string>
     */
    protected const array RECORD_KEYS = [
        OrderListMessage::class => Order::FIELD_NUMBER,
        TicketListMessage::class => TicketMessage::FIELD_NUMBER,
    ];

    /**
     * The page count key, added only for lists that read one.
     */
    protected const string FIELD_TOTAL_PAGES = 'totalpages';

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return mixed
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $page = [
            PagedListMessage::FIELD_TOTAL_ROWS => 1,
            PagedListMessage::FIELD_CURRENT_PAGE => 1,
            PagedListMessage::FIELD_PAGE_SIZE => 1,
            \constant($type . '::FIELD_RESULT') => [$data],
        ];
        // Only lists that read a page count get one, so a strict serializer doesn't refuse it.
        if (\defined($type . '::FIELD_TOTAL_PAGES')) {
            $page[self::FIELD_TOTAL_PAGES] = 1;
        }

        return $this->denormalizer->denormalize($page, $type, $format, $context);
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return bool
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = [],
    ): bool {
        $recordKey = static::RECORD_KEYS[$type] ?? null;

        return $recordKey !== null
            && \is_array($data)
            && !\array_key_exists(\constant($type . '::FIELD_RESULT'), $data)
            && \array_key_exists($recordKey, $data);
    }

    /**
     * @param string|null $format
     *
     * @return array<class-string|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return \array_fill_keys(\array_keys(static::RECORD_KEYS), false);
    }
}
