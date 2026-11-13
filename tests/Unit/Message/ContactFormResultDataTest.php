<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormResultDataMessage;

/**
 * A contact form's generated document comes as a `data:` URL.
 */
#[CoversClass(ContactFormResultDataMessage::class)]
final class ContactFormResultDataTest extends TestCase
{
    public function testTheDocumentDecodesFromItsDataUrl(): void
    {
        $data = new ContactFormResultDataMessage(file: 'data:application/pdf;base64,aGVsbG8K', fileName: 'a.pdf');

        self::assertSame("hello\n", $data->getDecodedFile());
    }

    public function testNoDocumentDecodesToNull(): void
    {
        self::assertNull((new ContactFormResultDataMessage())->getDecodedFile());
    }

    public function testAValueThatIsNoBase64DataUrlDecodesToEmpty(): void
    {
        self::assertSame('', (new ContactFormResultDataMessage(file: 'aGVsbG8K'))->getDecodedFile());
    }
}
