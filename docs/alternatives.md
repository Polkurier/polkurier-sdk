Alternatives
============

Where the SDK offers several ways to do the same thing, this page lists them and says when to use
each. Most rows link to the full description.

Building the client
-------------------

| Route | Use when | Where |
|-------|----------|-------|
| `new PolkurierWebservice(new Config(…))` | Scripts, cron, small applications | [Getting started](getting-started.md) |
| Facade with substituted collaborators | You need your own HTTP client, serializer or validator | [Architecture](architecture.md#extension-points) |
| Facade with `services: [Foo::class => $bar]` | One service needs to behave differently | [Architecture](architecture.md#substituting-a-service) |
| Wiring a service by hand, no facade | A container is doing the wiring | [Architecture](architecture.md#not-using-the-facade-at-all) |

Whichever you choose, type-hint `Api\Service\*Interface` in your own code. Your code should not
depend on the facade.

Supplying configuration
-----------------------

| Route | Use when | Where |
|-------|----------|-------|
| A `Config` object | The values are known at wiring time | [Configuration](configuration.md#config) |
| Your own `ConfigInterface` | The values come from a settings store or parameter bag | [Configuration](configuration.md) |
| Your own `ConfigProviderInterface` | The values are not known until the call (per tenant, shop or channel) | [Configuration](configuration.md#per-tenant-credentials) |
| A client per tenant, sharing one serializer | Long-running workers, where a provider is not enough | [Configuration](configuration.md#sharing-a-serializer-across-clients) |

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
| Subclassing a message | The API grew a field the SDK has not caught up with | [Messages](messages.md#extending-a-message) |

All 4 go through the same validation at the same point.

Finding a pickup point
----------------------

| Route | Answers |
|-------|---------|
| `findCourierPoints()` | "Which points are near here, across these carriers?" |
| `getCourierPoint($id, $couriers)` | "Does this id still exist?" Nullable |
| `geo()->getMapToken()` | "Let the customer pick one on a map" |

See [Services](services.md#finding-a-pickup-point) for details.

Reading orders back
-------------------

| Route | Use for |
|-------|---------|
| `getStatus()` | One order's current state. Fast. |
| `getStatus(tracking: true)` | The same, plus the carrier's scan history. Slower. |
| `getOrders()` | Querying and reconciliation. Paged, filterable, full `Order` objects. |

Paging
------

| Route | Use when |
|-------|----------|
| `while ($page <= $list->getTotalPages())` | `OrderListMessage`, `MoneyBoxPayoutListMessage` |
| Stop when a page comes back short | `InvoiceListMessage`, `TicketListMessage`, `PrepaidHistoryMessage` (no page count) |
| A generator wrapping either | Application code that just wants to iterate |

[Recipes](recipes.md#paging-through-a-list) shows both loops.

Country coverage
----------------

| Route | Answers |
|-------|---------|
| `getSupportedCountries()` | "What does Polkurier serve?" Returns `Country[]` with regions |
| `getCountries()` | "Where does *this* carrier deliver?" Returns a code-to-name map |

Support
-------

| Route | Use for |
|-------|---------|
| `createTicket()` + `getTicketMessages()` | A conversation you own and can poll |
| `getContactForm()` + `saveContactForm()` | A one-way submission whose fields the API defines |

Knowledge base
--------------

| Route | Use when |
|-------|----------|
| `getIndex()` | You want everything, and will filter locally. Cacheable. |
| `search()` | The customer is typing. |

Invoices
--------

| Route | Gives you |
|-------|-----------|
| `getInvoicePdf()` | The document, for filing |
| `getInvoiceSummaryList()` | The itemised summary, for reconciling line by line |

Documents in general
--------------------

| Route | Gives you |
|-------|-----------|
| `getFile()` | Base64, as received. Use it for a `data:` URI or to pass the file on |
| `getDecodedFile()` | The raw bytes. Use it to write to disk or stream to a browser |

`getLabel()` takes a **list** of order numbers and returns one PDF, so printing 50 labels takes one
call.

Changing what gets validated
----------------------------

| Route | Takes away |
|-------|-----------|
| `validatePayloads: false` | Everything |
| Subclass `AttributeValidator`, filter `rulesFor()` | One rule |
| Register an extra validator on the registry | Nothing. Adds your own |
| Declare rules on your own message subclass | Nothing. Adds your own |
| Implement `ValidatorInterface` | Everything the SDK had |

[Validation](validation.md#changing-the-rules) shows all 5 with code.

Reading a rejection
-------------------

| Route | Gives you |
|-------|-----------|
| `ValidationException::getValidationErrors()` | Everything, keyed by wire field name |
| `ValidationException::getFieldErrors($field)` | One field |
| `ValidationException::getMessage()` | All of it flattened into a sentence |
| `WebserviceException::getErrorMessage()->getValidationErrors()` | The same shape, from the service side |

Because of the last row, you can display a local rejection and a server-side one with the same
code.

Changing how requests are made
------------------------------

| Route | Replaces |
|-------|----------|
| `ClientFactoryInterface` | The HTTP client (any PSR-18 client) |
| `RequestFactoryInterface` | How the envelope is built |
| `ResponseFactoryInterface` | How the envelope is read |
| `SerializerProviderInterface` | The whole mapping stack |
| `SerializerConfig` | Caching, JSON flags, timezone, strictness |
| Subclassing a service | One method |
| `services: [...]` with your own implementation | One service entirely |

See [Architecture: extension points](architecture.md#extension-points).

Logging
-------

| Route | Logs |
|-------|------|
| No logger | Nothing |
| A PSR-3 logger, `loggingEnabled: true` | Errors and transport failures |
| The same, plus `methodsToLog: [...]` | The full request and response of those methods, **including the token** |

Testing
-------

| Seam | Use when |
|------|----------|
| Inject `Api\Service\*Interface` and mock it | Most unit tests |
| `services: [Foo::class => $mock]` | Testing your flow through the facade |
| Replace `ClientFactoryInterface` | Testing that you talk to the API correctly |
| `AttributeValidator` on its own | Testing a payload, with no client at all |
| The live API with `rejectUnknownFields: true` | Catching a response that has grown a field |

[Testing](testing.md) covers each of these.
