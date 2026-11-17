<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\ParsedResponseInterface;
use Polkurier\Webservice\Enum\Ticket\TicketStatusEnum;
use Polkurier\Webservice\Http\Response\KeepsRawResponseTrait;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A support ticket.
 *
 * Only the call that asks for the timeline fills it in. Ticket listings leave it out.
 *
 * `is_read` describes the customer's side of the conversation. It is true once Polkurier has replied
 * at least once and nothing on the ticket is still unseen. A ticket with no replies yet is reported
 * as unread. Treat the flag as "awaiting your attention", not as a read receipt.
 *
 * @api
 */
final readonly class TicketMessage implements MessageInterface, ParsedResponseInterface
{
    use KeepsRawResponseTrait;

    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_NUMBER = 'number';
    public const string FIELD_SUBJECT = 'subject';
    public const string FIELD_STATUS = 'status';
    public const string FIELD_HAS_EMPLOYEE_RESPONSES = 'has_employee_responses';
    public const string FIELD_READ = 'is_read';
    public const string FIELD_DATE_CREATED = 'date_created';
    public const string FIELD_EMPLOYEE_NAME = 'employee_name';
    public const string FIELD_ORDERS = 'orders';
    public const string FIELD_COMPLAINTS = 'complaints';
    public const string FIELD_INVOICES = 'invoices';
    public const string FIELD_MESSAGES = 'messages';
    public const string FIELD_RESPONSE_TEMPLATE = 'response_template';
    /**-@#*/

    /**
     * @param string $id UUID, used to address the ticket
     * @param string $number human-readable reference
     * @param string $subject the topic the ticket was raised under
     * @param TicketStatusEnum|null $status
     * @param bool $hasEmployeeResponses whether Polkurier has replied at all
     * @param bool $read true when there are replies and none is unseen
     * @param \DateTimeInterface|null $dateCreated
     * @param string $employeeName first name of the handler, if assigned
     * @param TicketReferenceMessage[] $orders orders this ticket refers to
     * @param TicketReferenceMessage[] $complaints complaints this ticket refers to
     * @param TicketReferenceMessage[] $invoices invoices this ticket refers to
     * @param TicketTimelineItemMessage[] $messages the timeline, when the call asked for it
     * @param TicketResponseTemplateMessage|null $responseTemplate the fixed answers Polkurier's latest reply
     *        asks for, with the timeline only
     * @param string|null $rawResponse the body of the response, as the API sent it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        public string $number = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_SUBJECT)]
        public string $subject = '',

        /**
         * @var TicketStatusEnum|null null for a value the SDK doesn't know
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        public ?TicketStatusEnum $status = null,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_HAS_EMPLOYEE_RESPONSES)]
        public bool $hasEmployeeResponses = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_READ)]
        public bool $read = false,

        /**
         * @var \DateTimeInterface|null null if the response leaves it out
         */
        #[Serializer\SerializedName(self::FIELD_DATE_CREATED)]
        #[OmitWhenEmpty]
        public ?\DateTimeInterface $dateCreated = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_EMPLOYEE_NAME)]
        #[PersonalData]
        public string $employeeName = '',

        /**
         * @var TicketReferenceMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_ORDERS)]
        public array $orders = [],

        /**
         * @var TicketReferenceMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_COMPLAINTS)]
        public array $complaints = [],

        /**
         * @var TicketReferenceMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_INVOICES)]
        public array $invoices = [],

        /**
         * The timeline, as createTicket() and setTicketStatus() return it, and getTickets() when asked
         * for one ticket by id. A list of tickets leaves it out, and
         * {@see \Polkurier\Webservice\Api\Service\TicketsServiceInterface::getTicketMessages()}
         * returns it on its own.
         *
         * @var TicketTimelineItemMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGES)]
        public array $messages = [],

        /**
         * The fixed answers the latest reply asks for; a reply must be one of them.
         *
         * Comes only with the timeline, and only while the ticket's latest message is a reply from
         * Polkurier that asks such a question.
         *
         * @var TicketResponseTemplateMessage|null null when there is none
         */
        #[Serializer\SerializedName(self::FIELD_RESPONSE_TEMPLATE)]
        public ?TicketResponseTemplateMessage $responseTemplate = null,

        /**
         * @var string|null
         */
        #[Serializer\Ignore]
        private ?string $rawResponse = null,
    ) {}
}
