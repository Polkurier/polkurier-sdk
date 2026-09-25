<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Optional services that can be bought alongside carriage.
 *
 * Availability depends on the carrier and sometimes on the account. `available_carriers` lists
 * the supported services, except the SMS notifications. Asking for an unsupported service is an
 * error. Services are sent as a map of code to `true`.
 * {@see \Polkurier\Webservice\Request\Order\CreateOrderMessage} builds the map from a plain list.
 *
 * Conditions the SDK can't check locally:
 *
 * - WEEK_COLLECTION is for InPost parcel lockers only.
 * - COVER_ADDRESS_SENDER requires a cover address on the order.
 * - There are 2 independent recipient SMS notifications: one when the parcel is dispatched and
 *   one when it goes out for delivery. You can order either or both. Each has a _WITH_NAME
 *   variant that replaces the plain one (don't order both). It needs a default SMS sender name on
 *   the account, otherwise the order is refused with PARAMS_NOT_SET. If you order the named
 *   dispatch notification, an out-for-delivery notification you also ordered is sent under that
 *   name too. It doesn't work the other way round.
 * - Not every UPS service supports SMS notifications.
 */
enum AdditionalServiceEnum: string
{
    /**
     * Return of signed documents (ROD): the recipient signs paperwork that the
     * carrier brings back to the sender.
     */
    case RefundDocs = 'ROD';

    /**
     * The courier brings the printed label, so the sender need not print one.
     */
    case CourierWithLabel = 'COURIER_WITH_LABEL';

    /**
     * A regular weekly collection. InPost parcel lockers only. Requesting it
     * for any other carrier is rejected.
     */
    case WeekCollection = 'WEEK_COLLECTION';

    /**
     * Texts the recipient when the parcel is dispatched.
     */
    case SmsNotificationRecipient = 'SMS_NOTIFICATION_RECIPIENT';

    /**
     * Like SMS_NOTIFICATION_RECIPIENT, but sent under the
     * account's own sender name. The name must be set on the
     * account. Replaces the plain variant, so don't order both.
     */
    case SmsNotificationRecipientWithName = 'SMS_NOTIFICATION_RECIPIENT_WITH_NAME';

    /**
     * Texts the recipient when the parcel goes out for delivery.
     * Independent of SMS_NOTIFICATION_RECIPIENT. You can order
     * either or both.
     */
    case SmsNotificationRecipientOutForDelivery = 'SMS_NOTIFICATION_RECIPIENT_ORDER_ISSUED_FOR_DELIVERY';

    /**
     * Like SMS_NOTIFICATION_RECIPIENT_ORDER_ISSUED_FOR_DELIVERY, but
     * sent under the account's own sender name. The name must be set
     * on the account. Replaces the plain variant, so don't order both.
     */
    case SmsNotificationRecipientOutForDeliveryWithName = 'SMS_NOTIFICATION_RECIPIENT_ORDER_ISSUED_FOR_DELIVERY_WITH_NAME';

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
    case PhoneNotificationRecipient = 'PHONE_NOTIFICATION_RECIPIENT';

    /**
     * Prints an alternative return address on the label. Requires a cover
     * address on the order.
     */
    case CoverAddressSender = 'COVER_ADDRESS_SENDER';

    /**
     * Declares the shipment as tyres, which several carriers price separately.
     */
    case Tires = 'TIRES';

    /**
     * The courier carries the parcel to the door, not just to the kerb.
     */
    case BringingDeliveredParcel = 'BRINGING_DELIVERED_PARCEL';

    /**
     * Delivery on a Saturday.
     */
    case SaturdayDelivery = 'SATURDAY_DELIVERY';

    /**
     * The courier telephones before collecting from the sender.
     */
    case PhoneNotificationCollection = 'PHONE_NOTIFICATION_COLLECTION';

    /**
     * Delivery before an agreed time of day.
     */
    case DeliveryToTime = 'DELIVERY_TO_TIME';

    /**
     * Handed to a person, not left in a safe place.
     */
    case HandDelivery = 'HAND_DELIVERY';

    /**
     * Carriage of dangerous goods under the ADR agreement.
     */
    case Adr = 'ADR';

    /**
     * Handed to the addressee personally, against an identity check.
     * Stricter than HAND_DELIVERY, which any person may accept.
     */
    case DeliveryToOwnHands = 'DOSTAWA_DO_RAK_WLASNYCH';

    /**
     * The freight must not have anything stacked on it.
     */
    case DoNotStack = 'DO_NOT_STACK';

    /**
     * A tail lift is needed at the collection address.
     */
    case RequireLiftForLoad = 'REQUIRE_LIFT_FOR_LOAD';

    /**
     * A tail lift is needed at the delivery address.
     */
    case RequireLiftForUnload = 'REQUIRE_LIFT_FOR_UNLOAD';

    /**
     * The vehicle must be loaded from the side.
     */
    case SideLoad = 'SIDE_LOAD';

    /**
     * The vehicle must be unloaded from the side.
     */
    case SideUnload = 'SIDE_UNLOAD';
}
