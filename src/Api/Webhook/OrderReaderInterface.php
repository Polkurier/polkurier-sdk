<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Webhook;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Webhook\ReadEvent;

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
     * Reads the event, with how the order in it differed from what the SDK expects. It doesn't
     * check the hash, see {@see self::isAuthentic()}.
     *
     * An order that differs is read anyway, as
     * {@see \Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum} describes.
     *
     * @param string $body the request body, as it came
     *
     * @return ReadEvent
     * @throws InvalidArgumentException when the body isn't an order event
     * @throws \ArgumentCountError when the class the payload maps onto cannot be built from any
     *         payload, which is a defect in that class rather than in what arrived
     */
    public function read(string $body): ReadEvent;
}
