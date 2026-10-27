<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Optional services that can be bought alongside carriage.
 *
 * Which services you can order depends on the carrier and sometimes on the account. Asking for one
 * the carrier doesn't offer is an error. Services are sent as a map of code to `true`.
 * {@see \Polkurier\Webservice\Request\Order\CreateOrderMessage} builds the map from a plain list.
 *
 * {@see \Polkurier\Webservice\Response\Carrier\AdditionalData\CourierService} lists the services
 * `available_carriers` reports for a carrier.
 *
 * The recipient SMS notifications come in 2 independent kinds: one when the parcel is dispatched
 * and one when it goes out for delivery. You can order either or both. Each has a variant sent
 * under the account's default SMS sender name. The named variant replaces the plain one, so an
 * order has one or the other. If the account has no default sender name, the order is refused
 * with PARAMS_NOT_SET. If you order the named dispatch notification, an out-for-delivery
 * notification you also ordered is sent under that name too. It doesn't work the other way round.
 * Not every UPS service supports SMS notifications.
 */
enum AdditionalServiceEnum: string
{
    /**
     * Return of signed documents (ROD): the recipient signs paperwork that the
     * carrier brings back to the sender.
     */
    case ReturnOfDocuments = 'ROD';

    /**
     * The courier brings the printed label, so the sender need not print one.
     */
    case CourierBringsLabel = 'COURIER_WITH_LABEL';

    /**
     * Delivery at the weekend. For InPost parcel lockers. It can only be ordered
     * inside a weekly window, and is refused outside it.
     */
    case WeekendDelivery = 'WEEK_COLLECTION';

    /**
     * Texts the recipient when the parcel is dispatched.
     */
    case SmsOnDispatch = 'SMS_NOTIFICATION_RECIPIENT';

    /**
     * SmsOnDispatch sent under the account's default SMS sender name. Order it
     * instead of SmsOnDispatch, not as well.
     */
    case SmsOnDispatchWithSenderName = 'SMS_NOTIFICATION_RECIPIENT_WITH_NAME';

    /**
     * Texts the recipient when the parcel goes out for delivery. Independent of
     * SmsOnDispatch. You can order either or both.
     */
    case SmsOutForDelivery = 'SMS_NOTIFICATION_RECIPIENT_ORDER_ISSUED_FOR_DELIVERY';

    /**
     * SmsOutForDelivery sent under the account's default SMS sender name. Order it
     * instead of SmsOutForDelivery, not as well.
     */
    case SmsOutForDeliveryWithSenderName = 'SMS_NOTIFICATION_RECIPIENT_ORDER_ISSUED_FOR_DELIVERY_WITH_NAME';

    /**
     * No printed label. The sender shows a code at drop-off. The code is returned
     * as the order's shipment code.
     */
    case Labelless = 'LABELLESS';

    /**
     * Marks the shipment as fragile.
     */
    case HandleWithCare = 'HANDLE_WITH_CARE';

    /**
     * The recipient may inspect the contents before accepting.
     */
    case CheckContent = 'CHECK_CONTENT';

    /**
     * The courier telephones the recipient before delivering.
     */
    case PhoneCallBeforeDelivery = 'PHONE_NOTIFICATION_RECIPIENT';

    /**
     * Prints an alternative sender address on the label. Requires a cover
     * address on the order, and sending one orders this service too.
     */
    case CoverSenderAddress = 'COVER_ADDRESS_SENDER';

    /**
     * Declares the shipment as tyres, which several carriers price separately.
     */
    case Tires = 'TIRES';

    /**
     * The courier carries the parcel in to the door, not just to the kerb.
     */
    case CarryIn = 'BRINGING_DELIVERED_PARCEL';

    /**
     * Delivery on a Saturday.
     */
    case SaturdayDelivery = 'SATURDAY_DELIVERY';

    /**
     * The courier telephones the sender before collecting.
     */
    case PhoneCallBeforePickup = 'PHONE_NOTIFICATION_COLLECTION';

    /**
     * Delivery by a set time of day, such as 9:00 or 12:00.
     */
    case DeliveryByTime = 'DELIVERY_TO_TIME';

    /**
     * Orders the same service as DeliveryToOwnHands.
     */
    case HandDelivery = 'HAND_DELIVERY';

    /**
     * Handed to the addressee in person. HandDelivery orders the same service.
     */
    case DeliveryToOwnHands = 'DOSTAWA_DO_RAK_WLASNYCH';

    /**
     * Carriage of dangerous goods under the ADR agreement.
     */
    case DangerousGoods = 'ADR';

    /**
     * The freight must not have anything stacked on it.
     */
    case DoNotStack = 'DO_NOT_STACK';

    /**
     * A vehicle with a tail lift is needed at the collection address.
     */
    case TailLiftAtPickup = 'REQUIRE_LIFT_FOR_LOAD';

    /**
     * A vehicle with a tail lift is needed at the delivery address.
     */
    case TailLiftAtDelivery = 'REQUIRE_LIFT_FOR_UNLOAD';

    /**
     * The vehicle must be loaded from the side at the collection address.
     */
    case SideLoadAtPickup = 'SIDE_LOAD';

    /**
     * The vehicle must be unloaded from the side at the delivery address.
     */
    case SideUnloadAtDelivery = 'SIDE_UNLOAD';
}
