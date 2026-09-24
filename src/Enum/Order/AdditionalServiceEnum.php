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
 * Availability is per carrier and sometimes per account; `available_carriers` reports the
 * supported set, and asking for an unsupported one is an error rather than a no-op. Services are
 * sent as a map of code to `true` — {@see \Polkurier\Webservice\Request\Order\CreateOrderMessage}
 * builds that from a plain list.
 *
 * Three have conditions the SDK cannot check locally:
 *
 * - WEEK_COLLECTION is InPost parcel lockers only.
 * - SMS_NOTIFICATION_RECIPIENT_WITH_NAME needs a default SMS sender name on the account, and
 *   supersedes SMS_NOTIFICATION_RECIPIENT rather than combining with it.
 * - COVER_ADDRESS_SENDER requires a cover address on the order.
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
     * A standing weekly collection. InPost parcel lockers only; requesting it
     * for any other carrier is rejected.
     */
    case WeekCollection = 'WEEK_COLLECTION';

    /**
     * Texts the recipient about the delivery.
     */
    case SmsNotificationRecipient = 'SMS_NOTIFICATION_RECIPIENT';

    /**
     * As SMS_NOTIFICATION_RECIPIENT, but sent under the
     * account's own sender name. Requires that name to be
     * configured, and replaces the plain variant rather
     * than adding to it.
     */
    case SmsNotificationRecipientWithName = 'SMS_NOTIFICATION_RECIPIENT_WITH_NAME';

    /**
     * No printed label: the sender presents a code at drop-off. The code comes back
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
     * The courier carries the parcel to the door rather than the kerb.
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
     * Handed to a person rather than left in a safe place.
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
