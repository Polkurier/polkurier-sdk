<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Polkurier\Webservice\Dto\Request\Contact\GetContactFormMessage;
use Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessage;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessageMessage;
use Polkurier\Webservice\Dto\Request\Ticket\GetTicketMessageFileMessage;
use Polkurier\Webservice\Dto\Request\Ticket\GetTicketsMessage;
use Polkurier\Webservice\Dto\Request\Ticket\SetTicketStatusMessage;
use Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage;
use Polkurier\Webservice\Dto\Request\Ticket\TicketIdMessage;
use Polkurier\Webservice\Enum\Ticket\TicketStatusEnum;

/**
 * One ticket opened, answered with a file, read back and closed. A submitted contact form opens a
 * ticket that can't be closed through the API, so that test sends an answer the form rejects.
 */
#[Group('integration')]
#[CoversNothing]
final class TicketsTest extends IntegrationTestCase
{
    private const string TOPIC = 'fc8db0b4-acad-4f15-b2af-a066629bc002';
    private const string INVOICE_TOPIC = 'ecd74aa3-c084-4d05-b425-618bcb37d37a';

    public function testTheTopicsRead(): void
    {
        self::assertNotEmpty($this->sdk->tickets()->getContactTopics());
    }

    public function testAContactFormReads(): void
    {
        $form = $this->sdk->tickets()->getContactForm(new GetContactFormMessage(category: self::TOPIC));

        self::assertNotSame('', $form->nonce);
    }

    public function testARejectedContactFormAnswerIsReported(): void
    {
        $form = $this->sdk->tickets()->getContactForm(new GetContactFormMessage(category: self::INVOICE_TOPIC));

        $error = self::assertApiError(
            fn() => $this->sdk->tickets()->saveContactForm(new SaveContactFormMessage(
                category: self::INVOICE_TOPIC,
                formId: $form->id,
                nonce: $form->nonce,
                fields: ['invoice_number' => 'FV/0000/00/00000'],
            )),
            'VALIDATION_ERROR',
        );

        self::assertNotEmpty($error->nonce);
    }

    public function testTicketsReadAsAPage(): void
    {
        $this->assertReads(fn() => $this->sdk->tickets()->getTickets(new GetTicketsMessage(pageSize: 100, page: 1)));
    }

    public function testATicketIsOpenedWithAFile(): string
    {
        self::requireSandbox();

        $ticket = $this->sdk->tickets()->createTicket(new CreateTicketMessage(
            topicId: self::TOPIC,
            content: 'Test ticket sent by the Polkurier SDK integration tests.',
            name: 'Jan Kowalski',
            files: [self::attachment()],
        ));

        self::assertNotSame('', $ticket->id);

        return $ticket->id;
    }

    #[Depends('testATicketIsOpenedWithAFile')]
    public function testTheTicketReadsBackById(string $id): void
    {
        $page = $this->sdk->tickets()->getTickets(new GetTicketsMessage(id: $id));

        self::assertSame($id, $page->result[0]->id);
    }

    #[Depends('testATicketIsOpenedWithAFile')]
    public function testAReplyCarriesOnlyAFileAndTheFileReadsBack(string $id): void
    {
        $reply = $this->sdk->tickets()->createTicketMessage(
            new CreateTicketMessageMessage(ticketId: $id, content: '', files: [self::attachment()]),
        );
        $timeline = $this->sdk->tickets()->getTicketMessages(new TicketIdMessage(ticketId: $id));
        $entry = \array_values(\array_filter($timeline, static fn($item): bool => $item->id === $reply->id));
        self::assertNotEmpty($entry);
        self::assertNotEmpty($entry[0]->files);

        $file = $this->sdk->tickets()->getTicketMessageFile(new GetTicketMessageFileMessage(
            messageId: $reply->id,
            filename: $entry[0]->files[0]->name,
        ));

        self::assertSame("Example attachment\n", $file->file);
    }

    #[Depends('testATicketIsOpenedWithAFile')]
    public function testTheTicketIsClosed(string $id): void
    {
        $ticket = $this->sdk->tickets()->setTicketStatus(new SetTicketStatusMessage(ticketId: $id));

        self::assertSame(TicketStatusEnum::Closed, $ticket->status);
    }

    /**
     * @return TicketAttachmentMessage
     */
    private static function attachment(): TicketAttachmentMessage
    {
        return TicketAttachmentMessage::makeFromContents('note.txt', "Example attachment\n", 'text/plain');
    }
}
