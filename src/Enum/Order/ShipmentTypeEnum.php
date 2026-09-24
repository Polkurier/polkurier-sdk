<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * The broad shape of a shipment, sent as `shipmenttype` when pricing or placing an order.
 *
 * This is coarser than {@see PackTypeEnum}, which describes an individual parcel: an order has one
 * shipment type and one entry per parcel.
 */
enum ShipmentTypeEnum: string
{
    case Envelope = 'envelope';
    case Box = 'box';
    case Palette = 'palette';
}
