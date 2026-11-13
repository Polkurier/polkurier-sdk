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
use Polkurier\Webservice\Response\Ticket\TicketFileContentMessage;
use Polkurier\Webservice\Response\Ticket\TicketMessage;
use Polkurier\Webservice\Response\Ticket\TicketResponseTemplateMessage;
use Polkurier\Webservice\Serializer\SerializerConfig;
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

    public function testAWebOnlyFormReadsWithNothingButItsAddress(): void
    {
        $form = self::read(ContactFormMessage::class, [
            'has_form' => true, 'web_only' => true, 'href' => 'https://www.polkurier.pl/kontakt/abc',
        ], strict: true);

        self::assertInstanceOf(ContactFormMessage::class, $form);
        self::assertTrue($form->hasForm());
        self::assertTrue($form->isWebOnly());
        self::assertSame('https://www.polkurier.pl/kontakt/abc', $form->getHref());
        self::assertSame([], $form->getFields());
        self::assertSame('', $form->getNonce());
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

    public function testADownloadedTicketAttachmentReadsEveryFieldTheApiSends(): void
    {
        $file = self::read(TicketFileContentMessage::class, [
            'name' => 'zdjecie_1b4e28ba-2fa1-41d2-883f-0016d3cca427.jpg',
            'type' => 'image/jpeg',
            'size' => 3,
            'date_created' => '2026-09-23T09:10:13+02:00',
            'hash' => '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08',
            'file' => 'YWJj',
        ], strict: true);

        self::assertInstanceOf(TicketFileContentMessage::class, $file);
        self::assertSame('zdjecie_1b4e28ba-2fa1-41d2-883f-0016d3cca427.jpg', $file->getName());
        self::assertSame('2026-09-23', $file->getDateCreated()?->format('Y-m-d'));
        self::assertSame('9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08', $file->getHash());
        self::assertSame('abc', $file->getDecodedFile());
    }

    public function testATicketReadsTheAnswersItsLatestReplyAsksFor(): void
    {
        $ticket = self::read(TicketMessage::class, [
            'id' => '8f3c1e2a-0d4a-4c3e-9b8f-1a2b3c4d5e6f',
            'response_template' => ['message' => 'Czy paczka dotarła?', 'responses' => ['Tak', 'Nie']],
        ], strict: true);

        self::assertInstanceOf(TicketMessage::class, $ticket);
        self::assertEquals(
            new TicketResponseTemplateMessage('Czy paczka dotarła?', ['Tak', 'Nie']),
            $ticket->getResponseTemplate(),
        );

        $sample = self::sample(TicketMessage::class);
        unset($sample[TicketMessage::FIELD_RESPONSE_TEMPLATE]);
        $none = self::read(TicketMessage::class, $sample);
        self::assertInstanceOf(TicketMessage::class, $none);
        self::assertNull($none->getResponseTemplate());
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
     * A full account profile, as the API sends it, reads even when unknown fields are rejected.
     */
    public function testTheAccountProfileReadsWhenUnknownFieldsAreRejected(): void
    {
        $profile = [
            'id' => 12345, 'phone_pin' => '1357', 'display_name' => 'Jan Kowalski', 'email' => 'jan@example.com',
            'default_courier' => 'UPS', 'is_active' => true, 'is_regulations_accepted' => true,
            'is_new_regulations' => false, 'is_prices_list_accepted' => true,
            'is_couriers_regulations_accepted' => true, 'is_electronic_invoice_accepted' => true,
            'is_email_verified' => true, 'is_payer_data_complete' => true, 'is_verified' => true,
            'is_delete_requested' => false, 'has_phone_number' => true, 'require_account_verification' => false,
            'require_agreement_verification' => false, 'payment_type' => 'INVOICE', 'money_box_balance' => 0,
            'money_box_payout_available_balance' => 0, 'money_box_to_payout_balance' => 0, 'prepaid_balance' => 0,
            'invoice_balance' => 0, 'invoice_payment_account_number' => null, 'invoice_limit' => null,
            'invoice_limit_used' => null, 'invoice_rebate' => 0, 'bonuses_savings' => 0,
            'default_shipping_point_sender_INPOST_PACZKOMAT' => 'POZ01A',
            'default_shipping_point_sender_KURIER48' => null,
            'default_sender_point' => ['INPOST_PACZKOMAT' => 'POZ01A', 'POCZTEX_PUNKT' => null, 'DPD_POINT' => null],
            'pin_locks' => ['PIN_SETTINGS' => false, 'BANK_ACCOUNTS_EDIT' => false, 'COD_MONEY_BOX' => false],
            'settings' => ['user_delivery_pickup_myself' => false, 'cover_address_sender_checked' => false],
        ];
        $body = \json_encode(['status' => 'success', 'response' => $profile], \JSON_THROW_ON_ERROR);

        $info = (new ResponseFactory(new SerializerProvider(new SerializerConfig(strictResponses: true))))
            ->create(new Response(200, [], $body), AccountInfoMessage::class)
            ->getEnvelope()
            ->getResponse();

        self::assertInstanceOf(AccountInfoMessage::class, $info);
        self::assertSame('1357', $info->getPhonePin());
        self::assertSame('POZ01A', $info->getDefaultInpostSenderPoint());
        self::assertNull($info->getDefaultPocztexSenderPoint());
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
     * @param bool $strict whether a field the SDK doesn't map fails the read
     *
     * @return mixed
     */
    private static function read(string $format, array $payload, bool $strict = false): mixed
    {
        $body = \json_encode(['status' => 'success', 'response' => $payload], \JSON_THROW_ON_ERROR);

        return (new ResponseFactory(new SerializerProvider(new SerializerConfig(strictResponses: $strict))))
            ->create(new Response(200, [], $body), $format)
            ->getEnvelope()
            ->getResponse();
    }
}
