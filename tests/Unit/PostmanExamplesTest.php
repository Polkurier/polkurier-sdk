<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Serializer\ResponseDrift;
use Polkurier\Webservice\Test\Support\Responses;

/**
 * Every response the Postman collection shows still reads the way the SDK reads a live one.
 *
 * The saved examples are written by hand from what the API answered, and nothing afterwards makes
 * them follow either the API or the SDK. A stale one is copied into client code and reads as null
 * with a drift warning there, which is what `"usage_type": "MULTIPLE"` did against an API that
 * answers `MULTI`. This reads every saved body through the serializer the services use and fails
 * on whatever it has to report.
 *
 * The shape each body is read into comes from the service that calls the method, so neither the
 * method list nor the response classes are written down twice.
 */
#[CoversNothing]
final class PostmanExamplesTest extends TestCase
{
    /**
     * The one request the collection sends with no method, or a method the API does not have:
     * the group showing what the API answers then. Those examples exercise the envelope, which
     * carries no payload to read into a class.
     */
    private const string ENVELOPE_GROUP = 'Common errors';

    /**
     * @param string $body
     * @param list<string> $formats the shapes the SDK reads that method into
     *
     * @return void
     */
    #[DataProvider('savedExamples')]
    public function testASavedExampleReadsWithNothingToReport(string $body, array $formats): void
    {
        // A method the SDK offers in both a single and a list shape is read in each: the example
        // was saved for one of them, and the shape it was saved for is the one with less to say.
        $reported = null;
        foreach ($formats as $format) {
            $found = self::readDrift($format, $body);
            if ($reported === null || \count($found) < \count($reported)) {
                $reported = $found;
            }
        }

        self::assertSame([], $reported);
    }

    /**
     * A method in the collection that no service calls is either an endpoint the SDK does not
     * cover or a name that has moved, and in both cases nothing above reads its examples.
     *
     * @return void
     */
    public function testEverySavedSuccessExampleNamesAMethodTheSdkCalls(): void
    {
        $formats = self::readFormatsByMethod();
        $uncalled = [];
        foreach (self::readExamples() as $where => [$method, $body, $success]) {
            if ($success && !isset($formats[$method])) {
                $uncalled[$where] = $method;
            }
        }

        self::assertSame([], $uncalled);
    }

    /**
     * @return array<string, array{string, list<string>}> keyed by where in the collection the
     *         example sits
     */
    public static function savedExamples(): array
    {
        $formats = self::readFormatsByMethod();
        $cases = [];
        foreach (self::readExamples() as $where => [$method, $body, $success]) {
            if (isset($formats[$method])) {
                $cases[$where] = [$body, $formats[$method]];
            }
        }

        return $cases;
    }

    /**
     * What the serializer had to report about a body, each entry naming the field and the value.
     *
     * A read that fails outright is one entry of its own: the collection shows what a client can
     * expect to parse, so a body the SDK cannot read at all is the same kind of failure as one it
     * reads with a warning.
     *
     * @param string $format
     * @param string $body
     *
     * @return list<string>
     */
    private static function readDrift(string $format, string $body): array
    {
        try {
            $wrapper = Responses::readBody($format, $body);
        } catch (\Throwable $failure) {
            return [$failure::class . ': ' . $failure->getMessage()];
        }

        return \array_map(
            static fn(ResponseDrift $drift): string => \sprintf(
                '%s at "%s": %s, expected %s',
                $drift->type->value,
                $drift->path,
                \json_encode($drift->value, \JSON_UNESCAPED_UNICODE),
                $drift->expected ?? 'nothing',
            ),
            $wrapper->getDrift(),
        );
    }

    /**
     * Every saved example the collection holds.
     *
     * @return array<string, array{string, string, bool}> the API method, the body, and whether the
     *         body is a success, keyed by where in the collection the example sits
     */
    private static function readExamples(): array
    {
        $collection = \json_decode(
            (string)\file_get_contents(\dirname(__DIR__, 2) . '/docs/postman/polkurier-api.postman_collection.json'),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );

        $examples = [];
        self::walkRequests(
            \is_array($collection) ? $collection : [],
            '',
            static function (array $request, array $saved, string $where) use (&$examples): void {
                if (\str_contains($where, self::ENVELOPE_GROUP)) {
                    return;
                }
                $raw = $request['body']['raw'] ?? '';
                $sent = \json_decode(\is_string($raw) ? $raw : '', true);
                $body = $saved['body'];
                $read = \json_decode($body, true);
                $examples[$where] = [
                    \is_array($sent) && \is_string($sent['apimethod'] ?? null) ? $sent['apimethod'] : '',
                    $body,
                    \is_array($read) && ($read['status'] ?? null) === 'success',
                ];
            },
        );

        return $examples;
    }

    /**
     * Hands every saved example to the caller, with the request it was saved under.
     *
     * @param array<array-key, mixed> $node
     * @param string $where
     * @param callable(array<string, mixed>, array{body: string, name?: mixed}, string): void $found
     *
     * @return void
     */
    private static function walkRequests(array $node, string $where, callable $found): void
    {
        $name = $node['name'] ?? null;
        $path = \is_string($name) ? \ltrim($where . ' / ' . $name, ' /') : $where;

        $request = $node['request'] ?? null;
        $saved = $node['response'] ?? null;
        if (\is_array($request) && \is_array($saved)) {
            foreach ($saved as $example) {
                if (\is_array($example) && \is_string($example['body'] ?? null)) {
                    $label = $example['name'] ?? null;
                    $found($request, $example, $path . ' / ' . (\is_string($label) ? $label : 'example'));
                }
            }
        }

        foreach ($node as $child) {
            if (\is_array($child)) {
                self::walkRequests($child, $path, $found);
            }
        }
    }

    /**
     * The shapes the SDK reads each API method's response into, read off the services that call
     * them.
     *
     * @return array<string, list<string>> keyed by the method as it goes on the wire
     */
    private static function readFormatsByMethod(): array
    {
        $methods = self::readLiteralsOf(\dirname(__DIR__, 2) . '/src/Enum/Http/ApiMethodEnum.php');
        $helpers = self::readRequestHelpers();

        $formats = [];
        foreach (self::readServiceSources() as $source) {
            $imports = self::readImports($source);
            \preg_match_all(
                '/\$this->(\w+)\(\s*\n?\s*ApiMethodEnum::(\w+)(?:,\s*\n?\s*([\w\\\\]+)::class)?/',
                $source,
                $calls,
                \PREG_SET_ORDER,
            );
            foreach ($calls as $call) {
                // A helper that names no class, such as the one for an acknowledgement, leaves
                // the third group unmatched.
                [, $helper, $case] = $call;
                $class = $call[3] ?? '';
                $method = $methods[$case] ?? null;
                $shape = $helpers[$helper] ?? null;
                if ($method === null || $shape === null) {
                    continue;
                }
                $formats[$method][] = match ($shape) {
                    'class' => $imports[$class] ?? $class,
                    'class[]' => ($imports[$class] ?? $class) . '[]',
                    default => $shape,
                };
            }
        }

        return \array_map(
            static fn(array $found): array => \array_values(\array_unique($found)),
            $formats,
        );
    }

    /**
     * What each of {@see \Polkurier\Webservice\Service\AbstractService}'s request helpers asks for:
     * the class its caller names, a list of it, or a format that names no class.
     *
     * @return array<string, string>
     */
    private static function readRequestHelpers(): array
    {
        $source = (string)\file_get_contents(\dirname(__DIR__, 2) . '/src/Service/AbstractService.php');
        $literals = self::readLiteralsOf(\dirname(__DIR__, 2) . '/src/Api/SerializerInterface.php');

        $bodies = (array)\preg_split(
            '/\n    (?:protected|public|private) function (\w+)\(/',
            $source,
            flags: \PREG_SPLIT_DELIM_CAPTURE,
        );

        $helpers = [];
        for ($i = 1; $i < \count($bodies); $i += 2) {
            $asked = \preg_match(
                '/processRequest\(\s*\n?\s*\$apiMethod,\s*\n?\s*([^,\n]+),/',
                (string)$bodies[$i + 1],
                $matched,
            ) === 1 ? \trim($matched[1]) : '';
            $shape = match (true) {
                $asked === '$responseClass' => 'class',
                \str_starts_with($asked, '$itemClass') => 'class[]',
                \preg_match('/SerializerInterface::(\w+)/', $asked, $named) === 1 => $literals[$named[1]] ?? null,
                default => null,
            };
            if ($shape !== null) {
                $helpers[(string)$bodies[$i]] = $shape;
            }
        }

        return $helpers;
    }

    /**
     * The value of every `case X = '…'` and `const string X = '…'` a file declares, by name.
     *
     * @param string $path
     *
     * @return array<string, string>
     */
    private static function readLiteralsOf(string $path): array
    {
        \preg_match_all(
            "/(?:case|const string) (\w+) = '([^']*)'/",
            (string)\file_get_contents($path),
            $declared,
            \PREG_SET_ORDER,
        );

        return \array_column($declared, 2, 1);
    }

    /**
     * @param string $source
     *
     * @return array<string, string> the name each import is written under, by short name
     */
    private static function readImports(string $source): array
    {
        \preg_match_all('/^use ([\w\\\\]+);/m', $source, $imported);

        $imports = [];
        foreach ($imported[1] as $name) {
            $separator = \strrpos($name, '\\');
            $imports[$separator === false ? $name : \substr($name, $separator + 1)] = $name;
        }

        return $imports;
    }

    /**
     * @return list<string>
     */
    private static function readServiceSources(): array
    {
        $sources = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                \dirname(__DIR__, 2) . '/src/Service',
                \FilesystemIterator::SKIP_DOTS,
            ),
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $sources[] = (string)\file_get_contents($file->getPathname());
            }
        }

        return $sources;
    }
}
