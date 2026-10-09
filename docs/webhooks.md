Webhooks
========

Polkurier can tell your system when one of the account's orders changes. Set a webhook address
in the Polkurier panel and Polkurier posts an event there when an order is created, changes status
or is cancelled. The panel gives a secret for the webhook once the configuration is saved.

[`Webhook\OrderWebhookReader`](../src/Webhook/OrderWebhookReader.php) checks an event and reads it into an
[`OrderWebhookMessage`](../src/Response/Webhook/OrderWebhookMessage.php).

Receiving an event
------------------

```php
use Polkurier\Webservice\Webhook\OrderWebhookReader;

$body = file_get_contents('php://input');
$reader = new OrderWebhookReader();

if (!$reader->isAuthentic($body, $webhookSecret)) {
    http_response_code(403);
    exit;
}

$event = $reader->read($body);

// $event->getType()  OrderWebhookTypeEnum::Created, Updated or Cancelled, or null if unknown
// $event->getOrder() the order, as getOrders() gives it

refreshOrder($event->getOrder()->getNumber());

http_response_code(200);
echo OrderWebhookReader::ACKNOWLEDGEMENT;   // "OK"
```

Polkurier expects HTTP 200 with `OK` as the whole body. `examples/order_webhook.php` does the same
with a body saved to a file.

What an event looks like
------------------------

An event is a form-encoded POST with 3 fields:

| Field | Read as | Holds |
|-------|---------|-------|
| `type` | [`OrderWebhookMessage::getType()`](../src/Response/Webhook/OrderWebhookMessage.php) | `CREATED`, `UPDATED` or `CANCELLED`, see [`OrderWebhookTypeEnum`](../src/Enum/Webhook/OrderWebhookTypeEnum.php) |
| `data` | [`OrderWebhookMessage::getOrder()`](../src/Response/Webhook/OrderWebhookMessage.php) | The order, in the shape [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) gives it, as an [`Order`](../src/Response/Order.php) |
| `hash` | [`OrderWebhookMessage::getHash()`](../src/Response/Webhook/OrderWebhookMessage.php) | An HMAC-SHA256 of the other fields, form-encoded, keyed with the webhook's secret |

The order comes as nested form fields, e.g. `data[number]` and `data[sender][city]`, so every value
is a string, and values that are null or empty lists aren't sent at all.
[`OrderWebhookReader::read()`](../src/Webhook/OrderWebhookReader.php) reads them into the same [`Order`](../src/Response/Order.php) a call to the API would give.

The reader takes the request body, not `$_POST`. PHP fills `$_POST` only up to its
`max_input_vars` setting, 1000 fields by default, and an order with many items has more. The reader
decodes the body itself, without that limit.

Checking the hash
-----------------

[`OrderWebhookReader::isAuthentic()`](../src/Webhook/OrderWebhookReader.php) computes the hash the way the Polkurier panel describes it, from the
event's fields without `hash`, and compares it in constant time. It returns false for a body with
no hash, or for an empty secret.

Handling events
---------------

An event is a sign that the order changed rather than the full story. The same event can arrive
more than once, and the order's status is in the order, not only in the event's type. The latest
state comes from [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) by the order's number.

A type the SDK doesn't know yet reads as null, and the event still reads.
[`OrderWebhookReader::read()`](../src/Webhook/OrderWebhookReader.php) throws [`Exception\InvalidArgumentException`](../src/Exception/InvalidArgumentException.php) for a body that isn't an
order event, or whose order can't be read.
