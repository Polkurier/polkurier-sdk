<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Log;

use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Log\LoggedFileWriter;
use Polkurier\Webservice\Log\PersonalDataRedactor;

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

    public function testSavedFilesAreNamedToMatchThePublishedPattern(): void
    {
        $directory = \sys_get_temp_dir() . '/polkurier-sdk-test-' . \bin2hex(\random_bytes(4));

        try {
            (new LoggedFileWriter())->process(
                [
                    'file' => \base64_encode('%PDF-1.4'),
                    'files' => ['Faktura 12/2026 (kopia).pdf' => \base64_encode('%PDF-1.4'), '...' => \base64_encode('x')],
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

    public function testANumberIsPersonalOnlyInTheMethodsWhereItIs(): void
    {
        $redactor = new PersonalDataRedactor();
        $data = ['number' => '61109010140000071219812874', 'email' => 'jan@example.com'];

        self::assertSame(
            ['number' => '***', 'email' => '***'],
            $redactor->redact($data, ApiMethodEnum::GetBankAccounts),
        );
        self::assertSame(
            ['number' => '61109010140000071219812874', 'email' => '***'],
            $redactor->redact($data, ApiMethodEnum::GetOrders),
        );
    }
}
