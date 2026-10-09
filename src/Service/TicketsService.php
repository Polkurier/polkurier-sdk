<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Service;

use Polkurier\Webservice\Api\Service\TicketsServiceInterface;
use Polkurier\Webservice\Enum\Http\ApiMethodEnum;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;
use Polkurier\Webservice\Request\Contact\GetContactFormMessage;
use Polkurier\Webservice\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Request\Ticket\CreateTicketMessage;
use Polkurier\Webservice\Request\Ticket\CreateTicketMessageMessage;
use Polkurier\Webservice\Request\Ticket\GetTicketMessageFileMessage;
use Polkurier\Webservice\Request\Ticket\GetTicketsMessage;
use Polkurier\Webservice\Request\Ticket\SetTicketStatusMessage;
use Polkurier\Webservice\Request\Ticket\TicketIdMessage;
use Polkurier\Webservice\Response\Contact\ContactFormMessage;
use Polkurier\Webservice\Response\Contact\ContactTopicMessage;
use Polkurier\Webservice\Response\Ticket\TicketFileContentMessage;
use Polkurier\Webservice\Response\Ticket\TicketListMessage;
use Polkurier\Webservice\Response\Ticket\TicketMessage;
use Polkurier\Webservice\Response\Ticket\TicketTimelineItemMessage;

/**
 * @see TicketsServiceInterface
 */
class TicketsService extends AbstractService implements TicketsServiceInterface
{
    /**
     * @return ContactTopicMessage[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getContactTopics(): array
    {
        return $this->requestList(ApiMethodEnum::GetContactTopics, ContactTopicMessage::class);
    }

    /**
     * @param CreateTicketMessage $createTicketMessage
     *
     * @return TicketMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createTicket(CreateTicketMessage $createTicketMessage): TicketMessage
    {
        return $this->requestObject(
            ApiMethodEnum::CreateTicket,
            TicketMessage::class,
            $createTicketMessage,
        );
    }

    /**
     * @param GetTicketsMessage|null $getTicketsMessage
     *
     * @return TicketListMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getTickets(?GetTicketsMessage $getTicketsMessage = null): TicketListMessage
    {
        return $this->requestObject(
            ApiMethodEnum::GetTickets,
            TicketListMessage::class,
            $getTicketsMessage,
        );
    }

    /**
     * @param TicketIdMessage $ticketIdMessage
     *
     * @return TicketTimelineItemMessage[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getTicketMessages(TicketIdMessage $ticketIdMessage): array
    {
        return $this->requestList(
            ApiMethodEnum::GetTicketMessages,
            TicketTimelineItemMessage::class,
            $ticketIdMessage,
        );
    }

    /**
     * @param CreateTicketMessageMessage $createTicketMessageMessage
     *
     * @return TicketTimelineItemMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createTicketMessage(
        CreateTicketMessageMessage $createTicketMessageMessage
    ): TicketTimelineItemMessage {
        return $this->requestObject(
            ApiMethodEnum::CreateTicketMessage,
            TicketTimelineItemMessage::class,
            $createTicketMessageMessage,
        );
    }

    /**
     * @param SetTicketStatusMessage $setTicketStatusMessage
     *
     * @return TicketMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function setTicketStatus(SetTicketStatusMessage $setTicketStatusMessage): TicketMessage
    {
        return $this->requestObject(
            ApiMethodEnum::SetTicketStatus,
            TicketMessage::class,
            $setTicketStatusMessage,
        );
    }

    /**
     * @param GetTicketMessageFileMessage $getTicketMessageFileMessage
     *
     * @return TicketFileContentMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getTicketMessageFile(
        GetTicketMessageFileMessage $getTicketMessageFileMessage
    ): TicketFileContentMessage {
        return $this->requestObject(
            ApiMethodEnum::GetTicketMessageFile,
            TicketFileContentMessage::class,
            $getTicketMessageFileMessage,
        );
    }

    /**
     * @param GetContactFormMessage $getContactFormMessage
     *
     * @return ContactFormMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getContactForm(GetContactFormMessage $getContactFormMessage): ContactFormMessage
    {
        return $this->requestObject(ApiMethodEnum::GetContactForm, ContactFormMessage::class, $getContactFormMessage);
    }

    /**
     * @param SaveContactFormMessage $saveContactFormMessage
     *
     * @return bool
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveContactForm(SaveContactFormMessage $saveContactFormMessage): bool
    {
        return $this->requestAcknowledgement(ApiMethodEnum::SaveContactForm, $saveContactFormMessage);
    }
}
