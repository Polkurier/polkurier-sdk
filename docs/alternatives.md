Alternatives
============

Building the client
-------------------

| Route | Use when | Where |
|-------|----------|-------|
| [`new PolkurierWebservice(new Config(…))`](../src/PolkurierWebservice.php) | Scripts, cron, small applications | [Getting started](getting-started.md) |
| Facade with substituted collaborators | You need your own HTTP client, serializer or validator | [Architecture](architecture.md#extension-points) |
| Facade with `services: [Foo::class => $bar]` | One service needs to behave differently | [Architecture](architecture.md#substituting-a-service) |
| Wiring a service by hand, no facade | A container is doing the wiring | [Architecture](architecture.md#not-using-the-facade-at-all) |

Supplying configuration
-----------------------

| Route | Use when | Where |
|-------|----------|-------|
| A [`Config`](../src/Config.php) object | The values are known at wiring time | [Configuration](configuration.md#config) |
| Your own [`ConfigInterface`](../src/Api/ConfigInterface.php) | The values come from a settings store or parameter bag | [Configuration](configuration.md) |
| Your own [`ConfigProviderInterface`](../src/Api/ConfigProviderInterface.php) | The values are not known until the call (per tenant, shop or channel) | [Configuration](configuration.md#per-tenant-credentials) |
| A client per tenant, sharing one serializer | Workers where the provider can't tell the current tenant | [Configuration](configuration.md#sharing-a-serializer-across-clients) |

Choosing an endpoint
--------------------

| Route | Effect |
|-------|--------|
| Nothing | Production |
| `sandbox: true` | The sandbox host |
| `apiUrl: '…'` | That URL, overriding both |

Building a request payload
--------------------------

| Route | Use when | Where |
|-------|----------|-------|
| The constructor, named arguments | Every value is to hand | [Messages](messages.md#three-ways-to-build-one) |
| A [builder](builders.md) | Fields are conditional, or assembled across steps |
| `new Message(...$array)` | Input is already an associative array you have validated | [Messages](messages.md#three-ways-to-build-one) |
| Subclassing an order, address or another stored record | The API grew a field the SDK has not caught up with | [Messages](messages.md#extending-a-message) |

All 4 go through the same validation at the same point.

Finding a pickup point
----------------------

| Route | Answers |
|-------|---------|
| [`CourierServiceInterface::findCourierPoints()`](../src/Api/Service/CourierServiceInterface.php) | "Which points are near here, across these carriers?" |
| [`CourierServiceInterface::getCourierPoint($id, $couriers)`](../src/Api/Service/CourierServiceInterface.php) | "Does this id still exist?" Nullable |
| [`$client->geo()->getMapToken()`](../src/Api/Service/GeoServiceInterface.php) | "Let the customer pick one on a map" |

See [Services](services.md#finding-a-pickup-point) for details.

Reading orders back
-------------------

| Route | Use for |
|-------|---------|
| [`OrdersServiceInterface::getStatus()`](../src/Api/Service/OrdersServiceInterface.php) | One order's current state. Fast. |
| [`OrdersServiceInterface::getStatus()`](../src/Api/Service/OrdersServiceInterface.php) with `tracking: true` | The same, plus the carrier's scan history. Slower. |
| [`OrdersServiceInterface::getOrders()`](../src/Api/Service/OrdersServiceInterface.php) | Querying and reconciliation. Paged, filterable, full [`Order`](../src/Entity/Order/Order.php) objects. |

Paging
------

| Route | Use when |
|-------|----------|
| `while ($page <= $list->totalPages)` | [`OrderListMessage`](../src/Dto/Response/Order/OrderListMessage.php), [`MoneyBoxPayoutListMessage`](../src/Dto/Response/MoneyBox/MoneyBoxPayoutListMessage.php) |
| Stop when a page comes back short | [`InvoiceListMessage`](../src/Dto/Response/Invoice/InvoiceListMessage.php), [`TicketListMessage`](../src/Dto/Response/Ticket/TicketListMessage.php), [`PrepaidHistoryMessage`](../src/Dto/Response/Payment/PrepaidHistoryMessage.php) (no page count) |
| A generator wrapping either | Application code that just wants to iterate |

[Recipes](recipes.md#paging-through-a-list) shows both loops.

Country coverage
----------------

| Route | Answers |
|-------|---------|
| [`CourierServiceInterface::getSupportedCountries()`](../src/Api/Service/CourierServiceInterface.php) | "What does Polkurier serve?" Returns `Country[]` with regions |
| [`CourierServiceInterface::getCountries()`](../src/Api/Service/CourierServiceInterface.php) | "Where does *this* carrier deliver?" Returns a code-to-name map |

Support
-------

| Route | Use for |
|-------|---------|
| [`TicketsServiceInterface::createTicket()`](../src/Api/Service/TicketsServiceInterface.php) + [`TicketsServiceInterface::getTicketMessages()`](../src/Api/Service/TicketsServiceInterface.php) | A conversation you own and can poll |
| [`TicketsServiceInterface::getContactForm()`](../src/Api/Service/TicketsServiceInterface.php) + [`TicketsServiceInterface::saveContactForm()`](../src/Api/Service/TicketsServiceInterface.php) | A submission whose fields the API defines |

Knowledge base
--------------

| Route | Use when |
|-------|----------|
| [`KnowledgeBaseServiceInterface::getIndex()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | You want everything, and will filter locally. Cacheable. |
| [`KnowledgeBaseServiceInterface::search()`](../src/Api/Service/KnowledgeBaseServiceInterface.php) | The customer is typing. |

Invoices
--------

| Route | Gives you |
|-------|-----------|
| [`InvoicesServiceInterface::getInvoicePdf()`](../src/Api/Service/InvoicesServiceInterface.php) | The document, for filing |
| [`InvoicesServiceInterface::getInvoiceSummaryList()`](../src/Api/Service/InvoicesServiceInterface.php) | The itemised summary, as a PDF or a CSV file, for reconciling line by line |

Documents in general
--------------------

Every document response ([`DocumentMessage`](../src/Dto/Response/Order/DocumentMessage.php), [`OrderFileMessage`](../src/Dto/Response/Order/OrderFileMessage.php),
[`InvoiceDocumentMessage`](../src/Dto/Response/Invoice/InvoiceDocumentMessage.php), [`InvoiceSummaryListMessage`](../src/Dto/Response/Invoice/InvoiceSummaryListMessage.php) and
[`TicketFileContentMessage`](../src/Dto/Response/Ticket/TicketFileContentMessage.php)) holds the file's bytes in `$file`, decoded from the base64 the API sends.
For a `data:` URI or to pass the file on as base64, encode it again with `base64_encode()`.

[`OrdersServiceInterface::getLabel()`](../src/Api/Service/OrdersServiceInterface.php) takes a **list** of order numbers and returns one PDF, so printing 50 labels takes one
call.

Changing what gets validated
----------------------------

| Route | Takes away |
|-------|-----------|
| `validatePayloads: false` | Everything |
| Register an extra validator on the registry | Nothing. Adds your own |
| Declare rules on your own message subclass | Nothing. Adds your own |
| Implement [`ValidatorInterface`](../src/Api/Validation/ValidatorInterface.php) | Everything the SDK had |

[Validation](validation.md#changing-the-rules) describes each of them.

Reading a rejection
-------------------

| Route | Gives you |
|-------|-----------|
| [`ValidationException::getValidationErrors()`](../src/Exception/ValidationException.php) | Everything, keyed by wire field name |
| [`ValidationException::getFieldErrors($field)`](../src/Exception/ValidationException.php) | One field |
| [`ValidationException::getMessage()`](../src/Exception/ValidationException.php) | All of it flattened into a sentence |
| [`WebserviceException::getErrorMessage()->validationErrors`](../src/Exception/WebserviceException.php) | The same shape, from the service side |

Because of the last row, you can display a local rejection and a server-side one with the same
code.

Changing how requests are made
------------------------------

| Route | Replaces |
|-------|----------|
| [`ClientFactoryInterface`](../src/Http/ClientFactoryInterface.php) | The HTTP client (any PSR-18 client) |
| [`RequestFactoryInterface`](../src/Http/Request/RequestFactoryInterface.php) | How the envelope is built |
| [`ResponseFactoryInterface`](../src/Http/Response/ResponseFactoryInterface.php) | How the envelope is read |
| [`SerializerProviderInterface`](../src/Serializer/SerializerProviderInterface.php) | The whole mapping stack |
| [`SerializerConfig`](../src/Serializer/SerializerConfig.php) | Caching, JSON flags, timezone, strictness |
| Subclassing a service | One method, at the risk of changes in any release |
| `services: [...]` with your own implementation | One service entirely |

See [Architecture: extension points](architecture.md#extension-points).

Logging
-------

| Route | Logs |
|-------|------|
| No logger | Nothing |
| A PSR-3 logger, `loggingEnabled: true` | Errors, transport failures and responses that differ from the SDK |
| The same, plus `methodsToLog: [...]` | The full request and response of those methods |
| The same, plus `logSensitiveData`, `logPersonalData` or `logFiles` | Also the secrets, personal data or files that are otherwise left out |

Testing
-------

| Seam | Use when |
|------|----------|
| Inject `Api\Service\*Interface` and mock it | Most unit tests |
| `services: [Foo::class => $mock]` | Testing your flow through the facade |
| Replace [`ClientFactoryInterface`](../src/Http/ClientFactoryInterface.php) | Testing that you talk to the API correctly |
| [`AttributeValidator`](../src/Validator/AttributeValidator.php) on its own | Testing a payload, with no client at all |
| The live API with `strictResponses: true` | Catching a response that has changed |

[Testing](testing.md) covers each of these.
