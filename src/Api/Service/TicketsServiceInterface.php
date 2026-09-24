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
 * A ticket is a conversation about a shipment, an invoice or a complaint. The usual flow is
 * getContactTopics() to find the right topic, then createTicket(), then getTicketMessages() to
 * follow the thread and createTicketMessage() to reply.
 *
 * Attachments travel inline as base64 rather than through a separate upload. `get_system_info`
 * reports the size and count limits under `tickets`.
 *
 * Errors always arrive with HTTP 200 and `status: "error"`, so branch on the envelope and never on
 * the transport status. Both `status_code` and `error_code` are optional, so an error can carry
 * both, either, or neither. With the default configuration the SDK raises these rather than
 * returning them.
 */
interface TicketsServiceInterface
{
    /**
     * The topics a ticket can be raised under.
     *
     * A shallow tree, expressed through each topic's parent id rather than by nesting. Only a
     * topic whose `has_form` is set can actually carry a ticket; the rest group the others.
     *
     * Each topic carries suggested help articles and categories, which exist so a client can offer
     * self-service before a ticket is created rather than after.
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
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Contact\ContactTopicMessage}[]
     */
    public function getContactTopics(): array;

    /**
     * Raises a support ticket.
     *
     * The topic must be one getContactTopics() reported and must accept a form; anything else is
     * rejected.
     *
     * Attachments go inline as base64, keyed by filename, and are checked against the limits
     * `get_system_info` reports under `tickets`. Checking them client-side is worth it — the
     * rejection comes after the whole payload has been uploaded.
     *
     * The origin identifies which channel raised the ticket and defaults to API. A mobile client
     * should identify itself so the support team can see how the customer reached them.
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
     * Raised when an attachment is too large, too numerous, or of a type that is not accepted.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "…", "validation_errors": {"content": ["…"]}}
     * ```
     *
     * @param CreateTicketMessage $createTicketMessage
     *
     * @return TicketMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketMessage}
     */
    public function createTicket(CreateTicketMessage $createTicketMessage): TicketMessage;

    /**
     * Lists tickets, one page at a time.
     *
     * Page size is clamped to between 1 and 250 and defaults to 100 — a wider range than the order
     * list allows. As with the invoice list there is no `totalpages`.
     *
     * Supplying an id narrows the list to that one ticket; the envelope is unchanged, so the
     * ticket still arrives inside `result`.
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
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketListMessage}
     */
    public function getTickets(?GetTicketsMessage $getTicketsMessage = null): TicketListMessage;

    /**
     * Fetches one ticket with its full conversation.
     *
     * The timeline mixes two kinds of entry: messages someone wrote, and actions that happened to
     * the ticket such as a status change. The type distinguishes them, and for an action the
     * content describes the event rather than quoting a person.
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
     * Raised for an unknown ticket and for one belonging to another account alike.
     *
     * @param TicketIdMessage $ticketIdMessage
     *
     * @return TicketMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketMessage}, with its timeline
     */
    public function getTicketMessages(TicketIdMessage $ticketIdMessage): TicketMessage;

    /**
     * Adds a reply to an existing ticket.
     *
     * Subject to the same attachment limits as raising one. A closed ticket cannot be replied to;
     * raise a new one instead.
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
     * Raised when the ticket is closed.
     *
     * @param CreateTicketMessageMessage $createTicketMessageMessage
     *
     * @return TicketMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketMessage}
     */
    public function createTicketMessage(
        CreateTicketMessageMessage $createTicketMessageMessage
    ): TicketMessage;

    /**
     * Changes a ticket's status.
     *
     * Only CLOSED is accepted — a customer may close their own ticket and nothing else. The other
     * statuses are reported on a ticket but cannot be set here, and sending one is rejected naming
     * the acceptable set.
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
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketMessage}
     */
    public function setTicketStatus(SetTicketStatusMessage $setTicketStatusMessage): TicketMessage;

    /**
     * Downloads one attachment from a ticket conversation.
     *
     * Attachments are addressed by the timeline entry they belong to plus their filename, so a
     * filename only has to be unique within its own message. Both come from the timeline that
     * getTicketMessages() returns.
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
     * Raised for an unknown message, an unknown filename within it, and a message on someone
     * else's ticket alike.
     *
     * @param GetTicketMessageFileMessage $getTicketMessageFileMessage
     *
     * @return TicketFileContentMessage payload:
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     *         {@see \Polkurier\Webservice\Response\Ticket\TicketFileContentMessage}
     */
    public function getTicketMessageFile(
        GetTicketMessageFileMessage $getTicketMessageFileMessage
    ): TicketFileContentMessage;

    /**
     * Fetches the definition of a contact form.
     *
     * The contact forms are a lighter-weight route than a ticket, used for enquiries that are not
     * about a specific shipment. The definition describes the fields to render and carries a form
     * id and a nonce that must be sent back with the submission.
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
     * Response: the form definition, whose field set varies per category, so the SDK leaves it as
     * decoded JSON rather than modelling a shape that would not hold.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [category]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * Raised for a category that has no form attached.
     *
     * @param GetContactFormMessage $getContactFormMessage
     *
     * @return array<array-key, mixed> the form definition; its fields vary per category
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
     * The form id and nonce come from the getContactForm() response and tie the submission to the
     * definition that was rendered, so a form cannot be replayed and cannot be submitted against a
     * definition that has since changed. Fetch the form immediately before submitting rather than
     * caching it.
     *
     * Endpoint: `save_contact_form`
     *
     * Request; the field names beyond these three come from the form definition:
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
     * Raised when the nonce is stale — fetch the form again and resubmit.
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
