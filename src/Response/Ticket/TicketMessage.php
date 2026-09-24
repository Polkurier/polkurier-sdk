<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Ticket;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Ticket\TicketStatusEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * A support ticket.
 *
 * The timeline is only populated by the call that asks for it; listing tickets returns them without
 * it.
 *
 * `is_read` is about the customer's side of the conversation: it is true once there is at least one
 * reply from Polkurier and nothing on the ticket is still unseen. A ticket with no replies yet is
 * therefore reported as unread, which is what makes it usable as an "awaiting your attention"
 * indicator rather than a literal read receipt.
 */
readonly class TicketMessage implements MessageInterface
{
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
    /**-@#*/

    /**
     * @param string $id UUID, used to address the ticket
     * @param string $number human-readable reference
     * @param string $subject the topic the ticket was raised under
     * @param TicketStatusEnum $status
     * @param bool $hasEmployeeResponses whether Polkurier has replied at all
     * @param bool $read true when there are replies and none is unseen
     * @param ?\DateTimeInterface $dateCreated
     * @param string $employeeName first name of the handler, if assigned
     * @param TicketReferenceMessage[] $orders orders this ticket refers to
     * @param TicketReferenceMessage[] $complaints complaints this ticket refers to
     * @param TicketReferenceMessage[] $invoices invoices this ticket refers to
     * @param TicketTimelineItemMessage[] $messages the timeline, when the call asked for it
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NUMBER)]
        private string $number = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_SUBJECT)]
        private string $subject = '',

        /**
         * @var TicketStatusEnum
         */
        #[Serializer\SerializedName(self::FIELD_STATUS)]
        private TicketStatusEnum $status = TicketStatusEnum::Opened,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_HAS_EMPLOYEE_RESPONSES)]
        private bool $hasEmployeeResponses = false,

        /**
         * @var bool
         */
        #[Serializer\SerializedName(self::FIELD_READ)]
        private bool $read = false,

        /**
         * @var ?\DateTimeInterface
         */
        #[Serializer\SerializedName(self::FIELD_DATE_CREATED)]
        #[OmitWhenEmpty]
        private ?\DateTimeInterface $dateCreated = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_EMPLOYEE_NAME)]
        private string $employeeName = '',

        /**
         * @var TicketReferenceMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_ORDERS)]
        private array $orders = [],

        /**
         * @var TicketReferenceMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_COMPLAINTS)]
        private array $complaints = [],

        /**
         * @var TicketReferenceMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_INVOICES)]
        private array $invoices = [],

        /**
         * @var TicketTimelineItemMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_MESSAGES)]
        private array $messages = [],
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return TicketStatusEnum
     */
    public function getStatus(): TicketStatusEnum
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function hasEmployeeResponses(): bool
    {
        return $this->hasEmployeeResponses;
    }

    /**
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->read;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    /**
     * @return string
     */
    public function getEmployeeName(): string
    {
        return $this->employeeName;
    }

    /**
     * @return TicketReferenceMessage[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @return TicketReferenceMessage[]
     */
    public function getComplaints(): array
    {
        return $this->complaints;
    }

    /**
     * @return TicketReferenceMessage[]
     */
    public function getInvoices(): array
    {
        return $this->invoices;
    }

    /**
     * @return TicketTimelineItemMessage[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
