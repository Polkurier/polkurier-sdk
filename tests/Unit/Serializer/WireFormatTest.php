<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Dto\Response\Account\ExternalApplicationSettingMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormResultDataMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormValueMessage;
use Polkurier\Webservice\Dto\Response\Order\DocumentMessage;
use Polkurier\Webservice\Enum\Serializer\ResponseDriftTypeEnum;
use Polkurier\Webservice\Serializer\Attribute\Base64;
use Polkurier\Webservice\Serializer\ResponseDriftCollector;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\WireFormatDenormalizer;
use Polkurier\Webservice\Test\Support\Sample;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Fields whose wire format differs from the value the property holds read as that value.
 */
#[CoversClass(WireFormatDenormalizer::class)]
final class WireFormatTest extends TestCase
{
    private ResponseDriftCollector $drift;

    protected function setUp(): void
    {
        $this->drift = new ResponseDriftCollector();
    }

    public function testValuesKeyedByNameReadAsPairsWithCompoundValuesAsJson(): void
    {
        $form = $this->read(ContactFormMessage::class, [
            'id' => 'form-1',
            'properties' => ['COURIERS' => ['DPD', 'DHL'], 'hide_login_button' => true],
            'state' => [],
        ]);
        $account = $this->read(AccountInfoMessage::class, [
            'external_application_settings' => ['webhook_url' => 'https://shop.example.com', 'enabled' => true],
        ] + Sample::wire(AccountInfoMessage::class));

        self::assertInstanceOf(ContactFormMessage::class, $form);
        self::assertEquals([
            new ContactFormValueMessage('COURIERS', '["DPD","DHL"]'),
            new ContactFormValueMessage('hide_login_button', true),
        ], $form->properties);
        self::assertSame([], $form->state);
        self::assertInstanceOf(AccountInfoMessage::class, $account);
        self::assertEquals([
            new ExternalApplicationSettingMessage('webhook_url', 'https://shop.example.com'),
            new ExternalApplicationSettingMessage('enabled', true),
        ], $account->externalApplicationSettings);
    }

    public function testABase64FileReadsAsItsBytes(): void
    {
        $document = $this->read(DocumentMessage::class, ['file' => \base64_encode("%PDF-1.4\n")]);

        self::assertInstanceOf(DocumentMessage::class, $document);
        self::assertSame("%PDF-1.4\n", $document->file);
        self::assertSame([], $this->drift->all());
    }

    public function testAFileThatIsNoBase64ReadsAsEmptyAndIsReported(): void
    {
        $document = $this->read(DocumentMessage::class, ['file' => 'not base64!']);

        self::assertInstanceOf(DocumentMessage::class, $document);
        self::assertSame('', $document->file);
        self::assertCount(1, $this->drift->all());
        self::assertSame(ResponseDriftTypeEnum::CastValue, $this->drift->all()[0]->type);
        self::assertSame('file', $this->drift->all()[0]->path);
    }

    public function testAContactFormDocumentReadsFromItsDataUrl(): void
    {
        $data = $this->read(ContactFormResultDataMessage::class, [
            'file' => 'data:application/pdf;base64,aGVsbG8K', 'fileName' => 'a.pdf',
        ]);
        $none = $this->read(ContactFormResultDataMessage::class, ['ticket_number' => 'T-1']);
        $plain = $this->read(ContactFormResultDataMessage::class, ['file' => 'aGVsbG8K']);

        self::assertInstanceOf(ContactFormResultDataMessage::class, $data);
        self::assertSame("hello\n", $data->file);
        self::assertInstanceOf(ContactFormResultDataMessage::class, $none);
        self::assertNull($none->file);
        self::assertInstanceOf(ContactFormResultDataMessage::class, $plain);
        self::assertSame('', $plain->file);
        self::assertCount(1, $this->drift->all());
    }

    /**
     * `#[SerializedName]` takes its name positionally or as `serializedName:`. A field declared
     * the second way is found under the name it gives, not under the property's own name.
     *
     * @return void
     */
    public function testAFormatIsFoundUnderANameGivenAsANamedArgument(): void
    {
        $class = (new class('') {
            public function __construct(
                #[SerializedName(serializedName: 'file_b64')]
                #[Base64]
                public string $file,
            ) {}
        })::class;

        $read = $this->read($class, ['file_b64' => \base64_encode("%PDF-1.4\n")]);

        self::assertInstanceOf($class, $read);
        self::assertSame("%PDF-1.4\n", $read->file);
        self::assertSame([], $this->drift->all());
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $wire
     *
     * @return mixed
     */
    private function read(string $class, array $wire): mixed
    {
        return (new SerializerProvider())->getSerializer()->denormalize($wire, $class, 'json', [
            ResponseDriftCollector::CONTEXT_KEY => $this->drift,
        ]);
    }
}
