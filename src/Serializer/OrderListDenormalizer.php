<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Polkurier\Webservice\Response\Order;
use Polkurier\Webservice\Response\Order\OrderListMessage;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Reads a single order as a one-row page where a page of orders is expected.
 *
 * Filtered by order number, `get_orders` answers with the order itself instead of a page. Read
 * as a page, its fields would be ignored and the order would look like an empty page. This
 * normalizer wraps it in a page with that one order, so the result has the same shape either way.
 */
class OrderListDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

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
        return $this->denormalizer->denormalize([
            OrderListMessage::FIELD_TOTAL_ROWS => 1,
            OrderListMessage::FIELD_TOTAL_PAGES => 1,
            OrderListMessage::FIELD_CURRENT_PAGE => 1,
            OrderListMessage::FIELD_PAGE_SIZE => 1,
            OrderListMessage::FIELD_RESULT => [$data],
        ], $type, $format, $context);
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
        return $type === OrderListMessage::class
            && \is_array($data)
            && !\array_key_exists(OrderListMessage::FIELD_RESULT, $data)
            && \array_key_exists(Order::FIELD_NUMBER, $data);
    }

    /**
     * @param string|null $format
     *
     * @return array<class-string|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [OrderListMessage::class => false];
    }
}
