Postman collection
==================

Every API method the SDK sends, as raw Postman requests: to try the API before writing code, see
each response shape and compare what your integration sends with what the API expects.

| File | What it is |
|------|------------|
| [`polkurier-api.postman_collection.json`](polkurier-api.postman_collection.json) | The requests, their tests and a saved example response for each |
| [`polkurier-sandbox.postman_environment.json`](polkurier-sandbox.postman_environment.json) | `baseUrl`, `login`, `token` and `runManualRequests`, set up for the sandbox |

Setup
-----

1. In Postman, import both files.
2. Select the `Polkurier sandbox` environment and set `login` (your account number) and `token`
   (your API token).
3. Send any request, e.g. `Utils / test_auth_api / Success: credentials accepted`.

`baseUrl` points at the sandbox ([`Config::SANDBOX_API_URL`](../../src/Config.php)). Set it to
[`Config::API_URL`](../../src/Config.php) for production.

Layout
------

- One folder per service, named like the client's accessors: `Orders` is `$client->orders()`.
- One folder per API method inside it, named by its `apimethod` value, as in the
  [API method index](../api-methods.md).
- One request per variant inside that: a success request for each response shape and the errors a
  caller can run into. `Common errors` holds the errors any method can return.

Each method folder's description names the SDK method that sends it. Each request's description
says what the variant shows.

Running it
----------

Each request's tests check the response's `status` and, for an error, its `status_code` and
`error_code`. Requests that create a record keep its identifier in a collection variable that later
requests in the same service use, so run a service's folder top to bottom, e.g. in the Collection
Runner. Folders marked **Changes data** remove what they create, where the API allows it.

Manual requests are skipped unless `runManualRequests` is `true`. They need data or codes you supply,
such as a two-factor code, or change something the collection can't put back, such as the password.
Their saved example responses are illustrative.
