<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Webhook;

use Polkurier\Webservice\Dto\Response\Webhook\OrderEventMessage;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\ResponseDrift;

/**
 * Reads the order events Polkurier posts to the webhook address set in the Polkurier panel.
 *
 * An event is a form-encoded POST with the fields `type`, `data` (the order) and `hash`. Check it
 * with {@see self::isAuthentic()} against the secret the panel gives for the webhook, then read it
 * with {@see self::read()}. Answer within 30 seconds with HTTP 200 and
 * {@see self::ACKNOWLEDGEMENT} as the body.
 *
 * Both methods take the request body as it came, e.g. `file_get_contents('php://input')`, rather
 * than `$_POST`. A large order has more fields than PHP's `max_input_vars` lets `$_POST` hold.
 *
 * {@see self::getLastDrift()} reports on the event read last, so one reader handles one event at a
 * time.
 *
 * @api
 */
interface OrderReaderInterface
{
    /**
     * The body to answer an event with, along with HTTP 200.
     */
    public const string ACKNOWLEDGEMENT = 'OK';

    /**
     * Whether the event was signed with the webhook's secret.
     *
     * @param string $body the request body, as it came
     * @param string $secret the secret the Polkurier panel gives for the webhook
     *
     * @return bool false also when the body has no hash, or the secret is empty
     */
    public function isAuthentic(string $body, string $secret): bool;

    /**
     * Reads the event. It doesn't check the hash, see {@see self::isAuthentic()}.
     *
     * @param string $body the request body, as it came
     *
     * @return OrderEventMessage
     * @throws InvalidArgumentException when the body isn't an order event
     */
    public function read(string $body): OrderEventMessage;

    /**
     * How the order in the last event read differed from what this version of the SDK expects.
     *
     * The event is read anyway, as
     * {@see \Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum} describes.
     *
     * @return list<ResponseDrift> empty when the order was as expected, or nothing was read
     */
    public function getLastDrift(): array;
}
