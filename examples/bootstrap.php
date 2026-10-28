<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
/**
 * Shared setup for the examples in this directory.
 *
 * Each example is a standalone script:
 *
 *     POLKURIER_API_LOGIN=12345 POLKURIER_API_TOKEN=… php examples/available_carriers.php
 *
 * Generate an API token in the customer panel under Settings > API token after signing in at
 * https://www.polkurier.pl. Set POLKURIER_API_SANDBOX=1, or point POLKURIER_API_URL at another
 * host, while you are developing; the SDK targets the production API when neither is set.
 */
declare(strict_types=1);

use Polkurier\Webservice\Config;
use Polkurier\Webservice\Http\Request\AuthorizationMessage;
use Polkurier\Webservice\PolkurierWebservice;
use Polkurier\Webservice\Request\Ticket\TicketAttachmentMessage;

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
        platformVersion: '2.0.1',
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
 * Ticket attachments read from the given files.
 *
 * The media type is detected when the fileinfo extension is loaded. Without it the SDK rejects the
 * attachment before sending.
 *
 * @param list<string> $paths
 *
 * @return list<TicketAttachmentMessage>
 */
function attachments(array $paths): array
{
    return \array_map(static function (string $path): TicketAttachmentMessage {
        $contents = \file_get_contents($path);
        if ($contents === false) {
            \fwrite(STDERR, "Cannot read {$path}.\n");
            exit(1);
        }
        $type = \function_exists('mime_content_type') ? (string)\mime_content_type($path) : '';

        return TicketAttachmentMessage::fromContents(\basename($path), $contents, $type);
    }, $paths);
}

/**
 * Prints whatever a service method answered with.
 */
function dumpResponse(mixed $payload): void
{
    \var_dump($payload);
}
