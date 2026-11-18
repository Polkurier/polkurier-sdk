<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutListMessage;
use Polkurier\Webservice\Dto\Response\MoneyBox\MoneyBoxPayoutMessage;
use Polkurier\Webservice\Dto\Response\Order\OrderListMessage;
use Polkurier\Webservice\Dto\Response\PagedListMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketListMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketMessage;
use Polkurier\Webservice\Entity\Order;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Reads a single record as a one-row page where a page of records is expected.
 *
 * Filtered to one record, `get_orders` (by order number), `get_tickets` (by id) and
 * `get_money_box_payouts` (by payout number) return the record itself. Read as a page, it would
 * look empty; this wraps it in a one-row page.
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
        MoneyBoxPayoutListMessage::class => MoneyBoxPayoutMessage::FIELD_NUMBER,
    ];

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<string, mixed> $context
     *
     * @return mixed
     *
     * @throws InvalidArgumentException when the type is not a page
     * @throws SerializerException when the wrapped denormalizer refuses the data
     * @throws \TypeError when a constructor refuses what the denormalizer built
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!\is_subclass_of($type, PagedListMessage::class)) {
            throw new InvalidArgumentException(\sprintf(
                '%s is not a page, so the key its rows arrive under is unknown.',
                $type,
            ));
        }

        // Every paging number of a one-row page is 1, and only the lists that read a page count
        // get one, so a strict serializer is not handed a key its class does not take.
        $page = \array_fill_keys($type::readPagingFields(), 1);
        $page[$type::FIELD_RESULT] = [$data];

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
            && \is_subclass_of($type, PagedListMessage::class)
            && !\array_key_exists($type::FIELD_RESULT, $data)
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
