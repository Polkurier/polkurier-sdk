<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * The broad shape of a shipment, sent as `shipmenttype` when pricing or placing an order.
 *
 * {@see \Polkurier\Webservice\Enum\Order\PackTypeEnum} describes a single parcel. An order has one
 * shipment type and one pack type entry per parcel.
 */
enum ShipmentTypeEnum: string
{
    case Envelope = 'envelope';
    case Box = 'box';
    case Palette = 'palette';
}
