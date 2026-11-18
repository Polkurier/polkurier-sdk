Webhooks
========

Polkurier can tell your system when one of the account's orders changes. Set a webhook address,
starting with `https://`, in the Polkurier panel and Polkurier posts an event there when an order
is created, changes status or is cancelled. The panel gives a secret for the webhook once the
configuration is saved.

[`Api\Webhook\OrderReaderInterface`](../src/Api/Webhook/OrderReaderInterface.php) checks an event and reads it into an
[`OrderEventMessage`](../src/Dto/Response/Webhook/OrderEventMessage.php). [`Webhook\OrderReader`](../src/Webhook/OrderReader.php) implements it, and a handler of your own can
type-hint the interface.

Receiving an event
------------------

```php
use Polkurier\Webservice\Webhook\OrderReader;

$body = file_get_contents('php://input');
$reader = new OrderReader();

if (!$reader->isAuthentic($body, $webhookSecret)) {
    http_response_code(403);
    exit;
}

$read  = $reader->read($body);
$event = $read->event;

// $event->type  OrderEventTypeEnum::Created, Updated or Cancelled, or null if unknown
// $event->order the order, as getOrders() gives it
// $read->drift  how the order differed from what the SDK expects

refreshOrder($event->order->getNumber());

http_response_code(200);
echo OrderReader::ACKNOWLEDGEMENT;   // "OK"
```

Polkurier expects HTTP 200 with `OK` as the whole body, within 30 seconds.
`examples/order_webhook.php` does the same with a body saved to a file.

Where you already have a client, [`PolkurierWebservice::orderReader()`](../src/PolkurierWebservice.php) hands out a reader built
with the [`Api\SerializerConfigInterface`](../src/Api/SerializerConfigInterface.php) that client was given, and keeps it for the next
event. Like a service, it is replaceable: pass your own under [`OrderReader`](../src/Webhook/OrderReader.php) in the client's
`services:` argument.

```php
$event = $client->orderReader()->read($body)->event;
```

The client passes that configuration on nowhere else, so this is how a reader comes to share
the client's settings: strict reading, the timezone for dates and the metadata cache. Constructed
directly, [`OrderReader`](../src/Webhook/OrderReader.php) takes a configuration of its own, and reads with the SDK's defaults when
given none.

What an event looks like
------------------------

An event is a form-encoded POST with 3 fields:

| Field | Read as | Holds |
|-------|---------|-------|
| `type` | [`OrderEventMessage::$type`](../src/Dto/Response/Webhook/OrderEventMessage.php) | `CREATED`, `UPDATED` or `CANCELLED`, see [`OrderEventTypeEnum`](../src/Enum/Webhook/OrderEventTypeEnum.php) |
| `data` | [`OrderEventMessage::$order`](../src/Dto/Response/Webhook/OrderEventMessage.php) | The order, in the shape [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) gives it, with parcels and items and without files, as an [`Order`](../src/Entity/Order.php) |
| `hash` | [`OrderEventMessage::$hash`](../src/Dto/Response/Webhook/OrderEventMessage.php) | An HMAC-SHA256 of the other fields, form-encoded, keyed with the webhook's secret |

The order comes as nested form fields, e.g. `data[number]` and `data[sender][city]`, so every value
is a string, and values that are null or empty lists aren't sent at all.
[`OrderReader::read()`](../src/Webhook/OrderReader.php) reads them into the same [`Order`](../src/Entity/Order.php) a call to the API would give.

The reader takes the request body, not `$_POST`. PHP fills `$_POST` only up to its
`max_input_vars` setting, 1000 fields by default, and an order with many items has more. The reader
decodes the body itself, without that limit.

Checking the hash
-----------------

[`OrderReader::isAuthentic()`](../src/Webhook/OrderReader.php) computes the hash the way the Polkurier panel describes it, from the
event's fields without `hash`, and compares it in constant time. It returns false for a body with
no hash, or for an empty secret.

Handling events
---------------

An event is a sign that the order changed rather than the full story. The same event can arrive
more than once, and the order's status is in the order, not only in the event's type. The latest
state comes from [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) by the order's number.

A type the SDK doesn't know yet reads as null, and the event still reads. An order that differs
from what the SDK expects reads as a response does (see
[Serialization](serialization.md#responses-that-differ-from-the-sdk)), and
[`ReadEvent::$drift`](../src/Webhook/ReadEvent.php) lists the differences in the event it came with. A field the form leaves
out because it is null isn't one.
[`OrderReader::read()`](../src/Webhook/OrderReader.php) throws [`Exception\InvalidArgumentException`](../src/Exception/InvalidArgumentException.php) for a body that isn't an
order event, for an order with none of an order's fields, and, with `strictResponses` on, for an
order that differs.
