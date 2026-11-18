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
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Serializer\Attribute\Base64;
use Polkurier\Webservice\Serializer\Attribute\Base64DataUrl;
use Polkurier\Webservice\Serializer\Attribute\NamedValues;
use Polkurier\Webservice\Serializer\Attribute\ResponseOnly;
use Polkurier\Webservice\Serializer\ResponseDriftCollector;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\WireFormatDenormalizer;
use Polkurier\Webservice\Test\Support\MessageClasses;
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

    /**
     * The three formats say how the API answers, and writing passes through undecoded, so a field
     * the SDK sends must not declare one: it would go out as the property holds it.
     *
     * @return void
     */
    public function testNothingTheSdkSendsDeclaresAWireFormat(): void
    {
        $declared = [];
        foreach (MessageClasses::requests() as $class) {
            foreach ((new \ReflectionClass($class))->getProperties() as $property) {
                if ($property->getAttributes(ResponseOnly::class) !== []) {
                    continue;
                }
                foreach ([Base64::class, Base64DataUrl::class, NamedValues::class] as $format) {
                    if ($property->getAttributes($format) !== []) {
                        $declared[] = $class . '::$' . $property->getName() . ' declares ' . $format;
                    }
                }
            }
        }

        self::assertSame([], $declared);
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
        self::assertSame([], $this->drift->getDrift());
    }

    public function testAFileThatIsNoBase64ReadsAsEmptyAndIsReported(): void
    {
        $document = $this->read(DocumentMessage::class, ['file' => 'not base64!']);

        self::assertInstanceOf(DocumentMessage::class, $document);
        self::assertSame('', $document->file);
        self::assertCount(1, $this->drift->getDrift());
        self::assertSame(ResponseDriftTypeEnum::CastValue, $this->drift->getDrift()[0]->type);
        self::assertSame('file', $this->drift->getDrift()[0]->path);
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
        self::assertCount(1, $this->drift->getDrift());
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
        self::assertSame([], $this->drift->getDrift());
    }

    /**
     * The formats exclude each other: read as plain base64, a `data:` URL yields its prefix as
     * bytes. A property declaring two says which one the API sends no better than none.
     *
     * @return void
     */
    public function testAPropertyDeclaringTwoFormatsIsRefusedAtTheFirstRead(): void
    {
        $class = (new class('') {
            public function __construct(
                #[Base64]
                #[Base64DataUrl]
                public string $file,
            ) {}
        })::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($class . '::$file');

        $this->read($class, ['file' => 'data:application/pdf;base64,aGVsbG8K']);
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
