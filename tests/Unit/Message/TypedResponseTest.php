<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Message;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Enum\Contact\ContactFormFieldTypeEnum;
use Polkurier\Webservice\Enum\Contact\ContactFormSeverityEnum;
use Polkurier\Webservice\Http\Response\ResponseFactory;
use Polkurier\Webservice\Response\Account\AccountInfoMessage;
use Polkurier\Webservice\Response\Account\ExternalApplicationSettingMessage;
use Polkurier\Webservice\Response\Contact\ContactFormMessage;
use Polkurier\Webservice\Response\Contact\ContactFormOptionMessage;
use Polkurier\Webservice\Response\Contact\ContactFormValueMessage;
use Polkurier\Webservice\Response\Contact\ContactTopicMessage;
use Polkurier\Webservice\Response\Contact\KnowledgeBaseLinkMessage;
use Polkurier\Webservice\Response\Order;
use Polkurier\Webservice\Response\Order\File;
use Polkurier\Webservice\Response\Order\ValuationMessage;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Test\Support\Sample;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Valuations, contact forms and their values read into typed messages, from payloads shaped as the
 * API sends them.
 */
#[CoversNothing]
final class TypedResponseTest extends TestCase
{
    public function testAValuationReadsAsOneQuotePerCarrier(): void
    {
        $quotes = self::read(ValuationMessage::class . '[]', [
            [
                'servicecode' => 'INPOST', 'serviceName' => 'InPost Kurier', 'netprice' => 15.12, 'grossprice' => 19,
                'conditional_price_nett' => 0, 'conditional_price_gross' => 0, 'promotion_nett' => 1.5,
                'promotion_gross' => 1.85, 'rebate_nett' => 0, 'rebate_gross' => 0, 'shipment' => true,
                'available' => true, 'unavailable_message' => '',
            ],
            [
                'servicecode' => 'DHL', 'serviceName' => 'DHL', 'netprice' => 0, 'grossprice' => 0,
                'conditional_price_nett' => 0, 'conditional_price_gross' => 0, 'promotion_nett' => 0,
                'promotion_gross' => 0, 'rebate_nett' => 0, 'rebate_gross' => 0, 'shipment' => false,
                'available' => false, 'unavailable_message' => 'Niedostępny',
            ],
        ]);

        self::assertIsArray($quotes);
        self::assertCount(2, $quotes);
        [$inpost, $dhl] = $quotes;
        self::assertInstanceOf(ValuationMessage::class, $inpost);
        self::assertInstanceOf(ValuationMessage::class, $dhl);
        self::assertSame('INPOST', $inpost->getServiceCode());
        self::assertSame('InPost Kurier', $inpost->getServiceName());
        self::assertSame(15.12, $inpost->getNetPrice());
        self::assertSame(19.0, $inpost->getGrossPrice());
        self::assertSame(1.85, $inpost->getPromotionGross());
        self::assertTrue($inpost->isShipment());
        self::assertFalse($dhl->isShipment());
        self::assertFalse($dhl->isAvailable());
        self::assertSame('Niedostępny', $dhl->getUnavailableMessage());
    }

    public function testAContactFormReadsItsFieldsChoicesAndValues(): void
    {
        $form = self::read(ContactFormMessage::class, [
            'id' => 'form-1',
            'fields' => [
                ['id' => 'f1', 'type' => 'TEXT', 'name' => 'name', 'value' => '', 'label' => 'Imię', 'maxLength' => 100],
                [
                    'id' => 'f2', 'type' => 'SELECT', 'name' => 'courier', 'value' => '', 'label' => 'Przewoźnik',
                    'options' => [
                        ['value' => 'DPD', 'label' => 'DPD'],
                        ['value' => '-1', 'label' => 'Inne', 'condition' => '@a != \'\''],
                    ],
                ],
                ['id' => 'f3', 'type' => 'SELECT', 'name' => 'target', 'value' => '', 'label' => 'Cel', 'options' => '@COURIERS'],
                [
                    'id' => 'f4', 'type' => 'ALERT', 'value' => '', 'label' => 'Uwaga', 'severity' => 'WARNING',
                    'condition' => '@courier IN @COURIERS', 'props' => ['fontSize' => 'small', 'inset' => true],
                ],
                [
                    'id' => 'f5', 'type' => 'FILES', 'name' => 'attachments[]', 'value' => '', 'label' => 'Pliki',
                    'maxLength' => 5, 'maxFileSize' => 10485760, 'allowedFileExtensions' => ['pdf', 'png'], 'props' => [],
                ],
                ['id' => 'f6', 'type' => 'NEW_KIND', 'name' => 'x', 'value' => '', 'label' => 'X'],
            ],
            'properties' => ['COURIERS' => ['DPD', 'DHL'], 'hide_login_button' => true],
            'state' => [],
            'nonce' => 'nonce-1',
            'has_form' => true,
        ]);

        self::assertInstanceOf(ContactFormMessage::class, $form);
        self::assertSame('form-1', $form->getId());
        self::assertSame('nonce-1', $form->getNonce());
        self::assertTrue($form->hasForm());
        self::assertFalse($form->isWebOnly());

        [$text, $select, $sourced, $alert, $files, $unknown] = $form->getFields();
        self::assertSame(ContactFormFieldTypeEnum::Text, $text->getType());
        self::assertSame(100, $text->getMaxLength());
        self::assertEquals(
            [new ContactFormOptionMessage('DPD', 'DPD'), new ContactFormOptionMessage('-1', 'Inne', '@a != \'\'')],
            $select->getOptions(),
        );
        self::assertSame('', $select->getOptionsSource());
        self::assertSame([], $sourced->getOptions());
        self::assertSame('@COURIERS', $sourced->getOptionsSource());
        self::assertSame(ContactFormSeverityEnum::Warning, $alert->getSeverity());
        self::assertSame('', $alert->getName());
        self::assertSame('@courier IN @COURIERS', $alert->getCondition());
        self::assertEquals(
            [new ContactFormValueMessage('fontSize', 'small'), new ContactFormValueMessage('inset', true)],
            $alert->getProps(),
        );
        self::assertSame(['pdf', 'png'], $files->getAllowedFileExtensions());
        self::assertSame(10485760, $files->getMaxFileSize());
        self::assertSame([], $files->getProps());
        self::assertNull($unknown->getType());

        self::assertEquals(
            [
                new ContactFormValueMessage('COURIERS', '["DPD","DHL"]'),
                new ContactFormValueMessage('hide_login_button', true),
            ],
            $form->getProperties(),
        );
        self::assertSame([], $form->getState());
    }

    public function testATopicWithoutAFormReadsAsSuch(): void
    {
        $form = self::read(ContactFormMessage::class, ['has_form' => false]);

        self::assertInstanceOf(ContactFormMessage::class, $form);
        self::assertFalse($form->hasForm());
        self::assertSame([], $form->getFields());
        self::assertSame('', $form->getNonce());
    }

    public function testAWebOnlyFormGivesItsAddress(): void
    {
        $form = self::read(ContactFormMessage::class, [
            'id' => 'form-2', 'fields' => [], 'properties' => [], 'state' => [], 'nonce' => 'n',
            'has_form' => true, 'web_only' => true, 'href' => 'https://www.polkurier.pl/kontakt/abc',
        ]);

        self::assertInstanceOf(ContactFormMessage::class, $form);
        self::assertTrue($form->isWebOnly());
        self::assertSame('https://www.polkurier.pl/kontakt/abc', $form->getHref());
    }

    public function testATopicReadsItsHelpLinks(): void
    {
        $topics = self::read(ContactTopicMessage::class . '[]', [[
            'id' => 't1', 'parent_id' => null, 'title' => 'Reklamacje', 'content' => '', 'has_form' => true,
            'knowledge_base_articles' => [['id' => 'jak-wykonac-zrzut-ekranu', 'title' => 'Jak wykonać zrzut ekranu?']],
            'knowledge_base_categories' => [['id' => 'reklamacje', 'title' => 'Reklamacje']],
        ]]);

        self::assertIsArray($topics);
        $topic = $topics[0];
        self::assertInstanceOf(ContactTopicMessage::class, $topic);
        self::assertEquals(
            [new KnowledgeBaseLinkMessage('jak-wykonac-zrzut-ekranu', 'Jak wykonać zrzut ekranu?')],
            $topic->getKnowledgeBaseArticles(),
        );
        self::assertEquals(
            [new KnowledgeBaseLinkMessage('reklamacje', 'Reklamacje')],
            $topic->getKnowledgeBaseCategories(),
        );
    }

    public function testAnOrderReadsItsFilesWithoutTheirContents(): void
    {
        $order = self::read(Order::class, [
            'files' => [
                [
                    'name' => 'faktura.pdf',
                    'type' => 'application/pdf',
                    'size' => 2048,
                    'hash' => 'abc',
                    'date_created' => '2026-10-01T12:00:00+02:00',
                ],
                ['name' => 'zdjecie.png', 'type' => 'image/png', 'size' => 10, 'hash' => 'def', 'date_created' => null],
            ],
        ] + self::sample(Order::class));

        self::assertInstanceOf(Order::class, $order);
        $files = $order->getFiles();
        self::assertIsArray($files);
        self::assertCount(2, $files);
        [$invoice, $photo] = $files;
        self::assertInstanceOf(File::class, $invoice);
        self::assertInstanceOf(File::class, $photo);
        self::assertSame('faktura.pdf', $invoice->getName());
        self::assertSame(2048, $invoice->getSize());
        self::assertSame('2026-10-01', $invoice->getDateCreated()?->format('Y-m-d'));
        self::assertNull($photo->getDateCreated());
    }

    public function testExternalApplicationSettingsReadAsNamedValues(): void
    {
        $sample = self::sample(AccountInfoMessage::class);
        $info = self::read(AccountInfoMessage::class, ['external_application_settings' => [
            'webhook_url' => 'https://shop.example.com/polkurier',
            'statuses' => ['NEW', 'SENT'],
            'enabled' => true,
        ]] + $sample);
        self::assertInstanceOf(AccountInfoMessage::class, $info);
        self::assertEquals(
            [
                new ExternalApplicationSettingMessage('webhook_url', 'https://shop.example.com/polkurier'),
                new ExternalApplicationSettingMessage('statuses', '["NEW","SENT"]'),
                new ExternalApplicationSettingMessage('enabled', true),
            ],
            $info->getExternalApplicationSettings(),
        );

        $none = self::read(AccountInfoMessage::class, ['external_application_settings' => []] + $sample);
        self::assertInstanceOf(AccountInfoMessage::class, $none);
        self::assertSame([], $none->getExternalApplicationSettings());

        unset($sample[AccountInfoMessage::FIELD_EXTERNAL_APPLICATION_SETTINGS]);
        $notAsked = self::read(AccountInfoMessage::class, $sample);
        self::assertInstanceOf(AccountInfoMessage::class, $notAsked);
        self::assertNull($notAsked->getExternalApplicationSettings());
    }

    /**
     * A fully populated response of the class, in its wire form.
     *
     * @param class-string $class
     *
     * @return array<array-key, mixed>
     */
    private static function sample(string $class): array
    {
        $serializer = (new SerializerProvider())->getSerializer();
        self::assertInstanceOf(NormalizerInterface::class, $serializer);
        $wire = $serializer->normalize(Sample::of($class), 'json');
        self::assertIsArray($wire);

        return $wire;
    }

    /**
     * @param string $format
     * @param array<array-key, mixed> $payload
     *
     * @return mixed
     */
    private static function read(string $format, array $payload): mixed
    {
        $body = \json_encode(['status' => 'success', 'response' => $payload], \JSON_THROW_ON_ERROR);

        return (new ResponseFactory(new SerializerProvider()))
            ->create(new Response(200, [], $body), $format)
            ->getParsedResponse()
            ->getResponse();
    }
}
