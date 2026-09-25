<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

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
use Polkurier\Webservice\Response\Contact\ContactTopicMessage;
use Polkurier\Webservice\Response\Ticket\TicketFileContentMessage;
use Polkurier\Webservice\Response\Ticket\TicketListMessage;
use Polkurier\Webservice\Response\Ticket\TicketMessage;

/**
 * Support tickets and the contact forms that feed them.
 *
 * A ticket is a conversation about a shipment, an invoice or a complaint. Usually you call
 * getContactTopics() to find the topic and createTicket() to open the ticket. Then use
 * getTicketMessages() to read the thread and createTicketMessage() to reply.
 *
 * Attachments are sent inline as base64. There is no separate upload. `get_system_info` reports
 * the size and count limits under `tickets`.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. With
 * the default configuration the SDK throws these as exceptions.
 */
interface TicketsServiceInterface
{
    /**
     * Lists the topics a ticket can be opened under.
     *
     * The topics form a shallow tree, given by each topic's parent id. Only topics with `has_form`
     * set can take a ticket. The others just group topics.
     *
     * Each topic lists suggested help articles and categories. You can show them to the customer
     * before they open a ticket.
     *
     * Endpoint: `get_contact_topics`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_contact_topics",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": []
     * }
     * ```
     *
     * Response, abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "przesylki",
     *             "parent_id": null,
     *             "title": "Przesyłki",
     *             "content": "Pytania dotyczące nadanych przesyłek",
     *             "has_form": true,
     *             "knowledge_base_articles": [
     *                 {"id": "…", "title": "Jak śledzić przesyłkę", "url": "https://…"}
     *             ],
     *             "knowledge_base_categories": [
     *                 {"id": "…", "title": "Nadawanie", "url": "https://…"}
     *             ]
     *         }
     *     ]
     * }
     * ```
     *
     * @return ContactTopicMessage[] payload:
     *         {@see \Polkurier\Webservice\Response\Contact\ContactTopicMessage}[]
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     */
    public function getContactTopics(): array;

    /**
     * Raises a support ticket.
     *
     * The topic must come from getContactTopics() and have `has_form` set. Any other topic is
     * rejected.
     *
     * Attachments go inline as base64, keyed by filename. They must fit the limits `get_system_info`
     * reports under `tickets`. Check them before sending.
     *
     * `origin` says which channel opened the ticket and defaults to API. A mobile client should send
     * its own origin.
     *
     * Endpoint: `create_ticket`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "create_ticket",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "topic_id": "przesylki",
     *         "name": "Jan Kowalski",
     *         "content": "Przesyłka 12345-678 nie dotarła.",
     *         "origin": "API",
     *         "files": {"zdjecie.jpg": "/9j/4AAQSkZJRgABAQ…"}
     *     }
     * }
     * ```
     *
     * Response: the created ticket, in the same shape getTickets() returns.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędna wartość parametru [origin]. Musi zawierać się w zbiorze [API, MOBILE_ANDROID, MOBILE_IOS]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 27, "error_code": "FILE_UPLOAD_FAIL", "response": "…"}
     * ```
     * Thrown when attachments are too large, too many, or of a type that isn't accepted.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"content": ["…"]}}
     * ```
     *
     * @param CreateTicketMessage $createTicketMessage
     *
     * @return TicketMessage payload:
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createTicket(CreateTicketMessage $createTicketMessage): TicketMessage;

    /**
     * Lists tickets, one page at a time.
     *
     * Page size must be 1–250 and defaults to 100. The SDK checks the range before sending. The order
     * list allows a narrower range. As with the invoice list, there is no `totalpages`.
     *
     * Pass an id to get just that ticket. The envelope stays the same, so the ticket is still inside
     * `result`.
     *
     * Tickets come back without their timeline. Use getTicketMessages() for the conversation.
     *
     * Endpoint: `get_tickets`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_tickets",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"id": "", "search_query": "", "pagesize": 100, "page": 1}
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "totalrows": 1,
     *         "currentpage": 1,
     *         "pagesize": 100,
     *         "result": [
     *             {
     *                 "id": "8f3c1e2a-…",
     *                 "number": "ZG/2026/00123",
     *                 "subject": "Przesyłki",
     *                 "status": "OPENED",
     *                 "has_employee_responses": false,
     *                 "is_read": false,
     *                 "date_created": "2026-09-23T09:10:13+02:00",
     *                 "employee_name": "",
     *                 "orders": [{"number": "12345-678", "waybill_number": "1Z999AA1"}],
     *                 "complaints": [],
     *                 "invoices": []
     *             }
     *         ]
     *     }
     * }
     * ```
     *
     * An account with no tickets gets a zeroed envelope with `"result": []`.
     *
     * @param GetTicketsMessage|null $getTicketsMessage null lists the first page unfiltered
     *
     * @return TicketListMessage payload:
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketListMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getTickets(?GetTicketsMessage $getTicketsMessage = null): TicketListMessage;

    /**
     * Fetches one ticket with its full conversation.
     *
     * The timeline has 2 kinds of entry, told apart by `type`: messages someone wrote, and actions
     * on the ticket such as a status change. For an action, `content` describes the event.
     *
     * Endpoint: `get_ticket_messages`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_ticket_messages",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"ticket_id": "8f3c1e2a-…"}
     * }
     * ```
     *
     * Response, abridged to the timeline:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "id": "8f3c1e2a-…",
     *         "number": "ZG/2026/00123",
     *         "status": "IN_PROGRESS",
     *         "messages": [
     *             {
     *                 "id": "…",
     *                 "type": "MESSAGE",
     *                 "date": "2026-09-23T09:10:13+02:00",
     *                 "origin": "CUSTOMER",
     *                 "author_name": "Jan",
     *                 "content": "Przesyłka nie dotarła.",
     *                 "date_read": null,
     *                 "files": [
     *                     {"name": "zdjecie.jpg", "type": "image/jpeg", "size": 102400,
     *                      "date_created": "2026-09-23T09:10:13+02:00"}
     *                 ]
     *             },
     *             {
     *                 "id": "…",
     *                 "type": "ACTION",
     *                 "date": "2026-09-23T10:00:00+02:00",
     *                 "origin": "EMPLOYEE",
     *                 "author_name": "Anna",
     *                 "content": "Zgłoszenie przypisane",
     *                 "date_read": null,
     *                 "files": []
     *             }
     *         ]
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * Thrown both for an unknown ticket and for one that belongs to another account.
     *
     * @param TicketIdMessage $ticketIdMessage
     *
     * @return TicketMessage payload:
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketMessage}, with its timeline
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getTicketMessages(TicketIdMessage $ticketIdMessage): TicketMessage;

    /**
     * Adds a reply to an existing ticket.
     *
     * The same attachment limits apply as for createTicket(). You can't reply to a closed ticket.
     * Open a new one instead.
     *
     * Endpoint: `create_ticket_message`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "create_ticket_message",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "ticket_id": "8f3c1e2a-…",
     *         "content": "Przesyłka nadal nie dotarła.",
     *         "files": {}
     *     }
     * }
     * ```
     *
     * Response: the ticket with the new entry on its timeline.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "…"}
     * ```
     * Thrown when the ticket is closed.
     *
     * @param CreateTicketMessageMessage $createTicketMessageMessage
     *
     * @return TicketMessage payload:
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createTicketMessage(
        CreateTicketMessageMessage $createTicketMessageMessage
    ): TicketMessage;

    /**
     * Changes a ticket's status.
     *
     * Only CLOSED is accepted: a customer can close their own ticket, nothing more. Other statuses
     * appear on tickets but can't be set here. Sending one gives an error that lists the accepted
     * values.
     *
     * Endpoint: `set_ticket_status`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "set_ticket_status",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"ticket_id": "8f3c1e2a-…", "status": "CLOSED"}
     * }
     * ```
     *
     * Response: the ticket with its new status.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędna wartość parametru [status]. Musi zawierać się w zbiorze [CLOSED]"}
     * ```
     *
     * @param SetTicketStatusMessage $setTicketStatusMessage
     *
     * @return TicketMessage payload:
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function setTicketStatus(SetTicketStatusMessage $setTicketStatusMessage): TicketMessage;

    /**
     * Downloads one attachment from a ticket conversation.
     *
     * An attachment is identified by its timeline entry id and its filename. A filename only has to
     * be unique within its message. Take both values from the timeline getTicketMessages() returns.
     *
     * Endpoint: `get_ticket_message_file`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_ticket_message_file",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"message_id": "…", "filename": "zdjecie.jpg"}
     * }
     * ```
     *
     * Response, with the base64 payload abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "name": "zdjecie.jpg",
     *         "type": "image/jpeg",
     *         "size": 102400,
     *         "file": "/9j/4AAQSkZJRgABAQ…"
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * Thrown for an unknown message, an unknown filename, or a message on another account's ticket.
     *
     * @param GetTicketMessageFileMessage $getTicketMessageFileMessage
     *
     * @return TicketFileContentMessage payload:
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketFileContentMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getTicketMessageFile(
        GetTicketMessageFileMessage $getTicketMessageFileMessage
    ): TicketFileContentMessage;

    /**
     * Fetches the definition of a contact form.
     *
     * Contact forms are a simpler alternative to tickets, for enquiries that aren't about a specific
     * shipment. The definition lists the fields to render. It also has a form id and a nonce, which
     * you must send back with the submission.
     *
     * Endpoint: `get_contact_form`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_contact_form",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"category": "wspolpraca"}
     * }
     * ```
     *
     * Response: the form definition. Its fields vary per category, so the SDK returns it as decoded
     * JSON.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [category]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * Thrown for a category that has no form.
     *
     * @param GetContactFormMessage $getContactFormMessage
     *
     * @return array<array-key, mixed> the form definition, whose fields vary per category
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getContactForm(GetContactFormMessage $getContactFormMessage): array;

    /**
     * Submits a completed contact form.
     *
     * Take the form id and nonce from the getContactForm() response. Fetch the form just before
     * submitting. Don't cache it.
     *
     * Endpoint: `save_contact_form`
     *
     * Request (any fields besides these 3 come from the form definition):
     * ```json
     * {
     *     "apimethod": "save_contact_form",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "category": "wspolpraca",
     *         "form_id": "…",
     *         "nonce": "…"
     *     }
     * }
     * ```
     *
     * Response:
     * ```json
     * {"status": "success", "response": []}
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 30, "error_code": "FORM_EXPIRED", "response": "…"}
     * ```
     * Thrown when the nonce is stale. Fetch the form again and resubmit.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"email": ["…"]}}
     * ```
     *
     * @param SaveContactFormMessage $saveContactFormMessage
     *
     * @return bool true on success
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveContactForm(SaveContactFormMessage $saveContactFormMessage): bool;
}
