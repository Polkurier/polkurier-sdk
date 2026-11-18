<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Log\LoggedFileWriter;

#[CoversClass(LoggedFileWriter::class)]
final class LoggedFileWriterTest extends TestCase
{
    public function testAttachmentsKeyedByNameAreLeftOutAndAListOfFileDescriptionsIsKept(): void
    {
        $data = [
            'files' => ['zdjecie.jpg' => \base64_encode("\xFF\xD8\xFFphoto")],
            'order' => ['files' => [['name' => 'protokol.pdf', 'size' => 10]]],
        ];

        $processed = (new LoggedFileWriter())->process($data, false, '/unused', ApiMethodEnum::CreateTicket);

        self::assertSame(['zdjecie.jpg' => '[file left out of the log: 8 bytes]'], $processed['files']);
        self::assertSame($data['order'], $processed['order']);
    }

    public function testAValueThatIsNotBase64IsKept(): void
    {
        $processed = (new LoggedFileWriter())->process(
            ['file' => 'label.pdf?'],
            false,
            '/unused',
            ApiMethodEnum::GetLabel,
        );

        self::assertSame(['file' => 'label.pdf?'], $processed);
    }

    /**
     * A saved file is a customer's document: a label carries the recipient's name and address.
     * The same fields are masked in the log line beside it, so the file must not be readable by
     * other accounts on the machine.
     *
     * @return void
     */
    public function testASavedFileIsReadableOnlyByTheProcessThatWroteIt(): void
    {
        $directory = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'polkurier-sdk-test-' . \bin2hex(\random_bytes(6));

        (new LoggedFileWriter())->process(
            ['filename' => 'label.pdf', 'file' => \base64_encode('%PDF-1.4 Jan Kowalski, Mickiewicza 5')],
            true,
            $directory,
            ApiMethodEnum::GetLabel,
        );

        $saved = \glob($directory . \DIRECTORY_SEPARATOR . '*') ?: [];
        self::assertCount(1, $saved);
        self::assertSame(0600, \fileperms($saved[0]) & 0777);
        self::assertSame(0700, \fileperms($directory) & 0777);

        foreach ($saved as $file) {
            \unlink($file);
        }
        \rmdir($directory);
    }

    public function testAFileThatCannotBeSavedIsNotedAndNothingIsThrown(): void
    {
        $blocker = \tempnam(\sys_get_temp_dir(), 'polkurier-sdk-test-');
        self::assertIsString($blocker);

        try {
            $processed = (new LoggedFileWriter())->process(
                ['file' => \base64_encode('%PDF-1.4')],
                true,
                $blocker . '/inside-a-file',
                ApiMethodEnum::GetLabel,
            );

            self::assertSame(
                ['file' => '[file left out of the log: 8 bytes, could not be saved in ' . $blocker . '/inside-a-file]'],
                $processed,
            );
        } finally {
            \unlink($blocker);
        }
    }

    /**
     * A body can spell the file's name under more than one key.
     * {@see LoggedFileWriter::NAME_FIELDS} lists them in order of precedence, and the saved file
     * takes the first of them the body carries.
     *
     * @return void
     */
    public function testAFileIsNamedAfterTheFirstNameKeyTheBodyCarries(): void
    {
        $directory = \sys_get_temp_dir() . '/polkurier-sdk-test-' . \bin2hex(\random_bytes(4));

        try {
            $processed = (new LoggedFileWriter())->process(
                [
                    'name' => 'etykieta.pdf',
                    'filename' => 'protokol.pdf',
                    'fileName' => 'faktura.pdf',
                    'file' => \base64_encode('%PDF-1.4'),
                ],
                true,
                $directory,
                ApiMethodEnum::GetLabel,
            );

            self::assertIsString($processed['file']);
            self::assertStringEndsWith('-etykieta.pdf: 8 bytes]', $processed['file']);
        } finally {
            \array_map('unlink', \glob($directory . '/*') ?: []);
            @\rmdir($directory);
        }
    }

    public function testSavedFilesAreNamedToMatchThePublishedPattern(): void
    {
        $directory = \sys_get_temp_dir() . '/polkurier-sdk-test-' . \bin2hex(\random_bytes(4));

        try {
            (new LoggedFileWriter())->process(
                [
                    'file' => \base64_encode('%PDF-1.4'),
                    'files' => [
                        'Faktura 12/2026 (kopia).pdf' => \base64_encode('%PDF-1.4'),
                        '...' => \base64_encode('x'),
                    ],
                ],
                true,
                $directory,
                ApiMethodEnum::CreateTicket,
            );

            $names = \array_map('basename', \glob($directory . '/*') ?: []);
            self::assertCount(3, $names);
            foreach ($names as $name) {
                self::assertMatchesRegularExpression(LoggedFileWriter::FILE_NAME_PATTERN, $name);
            }
        } finally {
            \array_map('unlink', \glob($directory . '/*') ?: []);
            @\rmdir($directory);
        }
    }
}
