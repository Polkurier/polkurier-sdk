<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
/**
 * Shared setup for the examples in this directory.
 *
 * Each example is a standalone script:
 *
 *     POLKURIER_API_LOGIN=12345 POLKURIER_API_TOKEN=… php examples/available_carriers.php
 *
 * Generate an API token in the customer panel under Settings > API token after signing in at
 * https://www.polkurier.pl. Point POLKURIER_API_URL at a sandbox while you are developing; the
 * SDK targets the production API when it is not set.
 */
declare(strict_types=1);

use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Builds a client from the environment.
 *
 * Logging is left at its default, which discards everything. Pass any PSR-3 logger as the second
 * constructor argument to see the requests and responses the SDK exchanges.
 */
function polkurier(): PolkurierWebservice
{
    $login = \getenv('POLKURIER_API_LOGIN');
    $token = \getenv('POLKURIER_API_TOKEN');

    if ($login === false || $token === false) {
        \fwrite(STDERR, "Set POLKURIER_API_LOGIN and POLKURIER_API_TOKEN before running this example.\n");
        exit(1);
    }

    $apiUrl = \getenv('POLKURIER_API_URL');

    return new PolkurierWebservice(new Config(
        authorization: new AuthorizationMessage(token: $token, login: $login),
        platform: 'polkurier-sdk-examples',
        platformVersion: '2.0.0',
        apiUrl: $apiUrl === false || $apiUrl === '' ? null : $apiUrl,
        sandbox: (bool)\getenv('POLKURIER_API_SANDBOX'),
    ));
}

/**
 * Whether the script was run with a given flag.
 *
 * Examples that write something real are guarded by one of these, so that running the whole
 * directory to see what each method does cannot place an order or move money.
 */
function hasFlag(string $flag): bool
{
    return \in_array($flag, $_SERVER['argv'] ?? [], true);
}

/**
 * The positional arguments, with the script name and any flags removed.
 *
 * @return list<string>
 */
function arguments(): array
{
    $argv = \array_slice($_SERVER['argv'] ?? [], 1);

    return \array_values(\array_filter(
        $argv,
        static fn(string $argument): bool => !\str_starts_with($argument, '--')
    ));
}

/**
 * Prints whatever a service method answered with.
 */
function dumpResponse(mixed $payload): void
{
    \var_dump($payload);
}
