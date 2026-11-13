<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Service;

use Polkurier\Webservice\Dto\Request\Contact\GetContactFormMessage;
use Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessage;
use Polkurier\Webservice\Dto\Request\Ticket\CreateTicketMessageMessage;
use Polkurier\Webservice\Dto\Request\Ticket\GetTicketMessageFileMessage;
use Polkurier\Webservice\Dto\Request\Ticket\GetTicketsMessage;
use Polkurier\Webservice\Dto\Request\Ticket\SetTicketStatusMessage;
use Polkurier\Webservice\Dto\Request\Ticket\TicketIdMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormResultMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactTopicMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketFileContentMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketListMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketMessage;
use Polkurier\Webservice\Dto\Response\Ticket\TicketTimelineItemMessage;
use Polkurier\Webservice\Exception\AuthorizationException;
use Polkurier\Webservice\Exception\ConnectionException;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Exception\MalformedResponseException;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Exception\WebserviceException;

/**
 * Support tickets and the contact forms that feed them.
 *
 * A ticket is a conversation about a shipment, an invoice or a complaint. Usually you call
 * getContactTopics() to find the topic and createTicket() to open the ticket. Then use
 * getTicketMessages() to read the thread and createTicketMessage() to reply.
 *
 * Attachments are sent inline as base64. `get_system_info` reports the size and count limits
 * under `tickets`.
 *
 * Errors come back with HTTP 200 and `status: "error"`, so check the envelope, not the HTTP status.
 * `status_code` and `error_code` are both optional: an error may have both, one or neither. The SDK
 * throws these as exceptions. Each method lists the ones it can throw.
 *
 * @api
 */
interface TicketsServiceInterface
{
    /**
     * Lists the topics a ticket or a contact form can be opened under.
     *
     * The topics form a tree, given by each topic's parent id. Which topics are listed depends on
     * the account. `has_form` tells whether getContactForm() has a form for the topic, and
     * `content` is an HTML fragment.
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
     *     "authorization": {"login": "12345", "token": "…"}
     * }
     * ```
     *
     * Response, abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "fc8db0b4-acad-4f15-b2af-a066629bc002",
     *             "parent_id": null,
     *             "title": "Przesyłki",
     *             "content": "<p>Pytania dotyczące nadanych przesyłek</p>",
     *             "has_form": true,
     *             "knowledge_base_articles": [
     *                 {"id": "jak-wykonac-zrzut-ekranu", "title": "Jak wykonać zrzut ekranu?"}
     *             ],
     *             "knowledge_base_categories": [
     *                 {"id": "reklamacje", "title": "Reklamacje"}
     *             ]
     *         }
     *     ]
     * }
     * ```
     *
     * @return ContactTopicMessage[] payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Contact\ContactTopicMessage}[]
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
     * Send a topic id from getContactTopics(). An id that matches no topic files the ticket under a
     * general subject.
     *
     * Attachments go inline as a list of files, each with its name, media type and base64 contents.
     * {@see \Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage} lists what the API
     * accepts.
     *
     * `origin` is the channel that opened the ticket, API by default.
     *
     * Endpoint: `create_ticket`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "create_ticket",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "topic_id": "fc8db0b4-acad-4f15-b2af-a066629bc002",
     *         "name": "Jan Kowalski",
     *         "content": "Przesyłka 12345-678 nie dotarła.",
     *         "origin": "API",
     *         "files": [
     *             {"name": "zdjecie.jpg", "type": "image/jpeg", "file": "/9j/4AAQSkZJRgABAQ…"}
     *         ]
     *     }
     * }
     * ```
     *
     * Response: the created ticket with its timeline, in the same shape getTickets() returns.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 24, "error_code": "PARAMETER_INVALID",
     *  "response": "Błędna wartość parametru [origin]. Musi zawierać się w zbiorze [API, MOBILE_IOS, MOBILE_ANDROID, MOBILE_ANDROID]"}
     * ```
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "Dozwolone są tylko pliki png, pdf, jpg, jpeg, gif, doc, docx, xls, xlsx, ppt, pptx, odt, ods, odp, csv, txt [files[type]]",
     *  "validation_errors": {"files[type]": ["…"]}}
     * ```
     * An attachment was refused: its name's extension under `files[type]`, its size under
     * `files[file]`, too many files under `files`.
     * ```json
     * {"status": "error", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "Treść nie może być pusta [content]", "validation_errors": {"content": ["…"]}}
     * ```
     * The content is empty and no file is attached.
     *
     * @param CreateTicketMessage $createTicketMessage
     *
     * @return TicketMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Ticket\TicketMessage}
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
     * With an id, the API returns that ticket alone, with its timeline. The SDK returns a one-row
     * page.
     *
     * Otherwise tickets come back without their timeline. Use getTicketMessages() for the
     * conversation. The list leaves out tickets closed more than 60 days ago; asking by id still
     * finds them.
     *
     * Endpoint: `get_tickets`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_tickets",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"pagesize": 100, "page": 1}
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
     *                 "number": "202609-000123",
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
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono zgłoszenia"}
     * ```
     * Thrown when the id matches no ticket on the account.
     *
     * @param GetTicketsMessage|null $getTicketsMessage null lists the first page unfiltered
     *
     * @return TicketListMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Ticket\TicketListMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getTickets(?GetTicketsMessage $getTicketsMessage = null): TicketListMessage;

    /**
     * Fetches a ticket's conversation, newest entry first.
     *
     * The timeline has 2 kinds of entry, told apart by `type`: messages someone wrote, and actions
     * on the ticket such as a status change. For an action, `content` describes the event, and it
     * comes without files. Use getTickets() for the ticket itself.
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
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": [
     *         {
     *             "id": "…",
     *             "type": "ACTION",
     *             "date": "2026-09-23T10:00:00+02:00",
     *             "origin": "EMPLOYEE",
     *             "author_name": "Anna",
     *             "content": "Zgłoszenie przypisane",
     *             "date_read": null
     *         },
     *         {
     *             "id": "…",
     *             "type": "MESSAGE",
     *             "date": "2026-09-23T09:10:13+02:00",
     *             "origin": "CUSTOMER",
     *             "author_name": "Jan",
     *             "content": "Przesyłka nie dotarła.",
     *             "date_read": null,
     *             "files": [
     *                 {"name": "zdjecie_1b4e28ba-2fa1-41d2-883f-0016d3cca427.jpg", "type": "image/jpeg",
     *                  "size": 102400, "date_created": "2026-09-23T09:10:13+02:00"}
     *             ]
     *         }
     *     ]
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
     * @return TicketTimelineItemMessage[] the timeline, see
     *         {@see \Polkurier\Webservice\Dto\Response\Ticket\TicketTimelineItemMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getTicketMessages(TicketIdMessage $ticketIdMessage): array;

    /**
     * Adds a reply to an existing ticket.
     *
     * The same attachment limits apply as for createTicket(). You can't reply to a closed ticket.
     * Open a new one instead.
     *
     * When the ticket has a `response_template`, the reply must be one of its `responses`; anything
     * else is a VALIDATION_ERROR on `content`.
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
     *         "files": []
     *     }
     * }
     * ```
     *
     * Response: the new timeline entry.
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "id": "…",
     *         "type": "MESSAGE",
     *         "date": "2026-09-23T11:00:00+02:00",
     *         "origin": "CUSTOMER",
     *         "author_name": "Jan",
     *         "content": "Przesyłka nadal nie dotarła.",
     *         "date_read": null,
     *         "files": []
     *     }
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono obiektu"}
     * ```
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "Zgłoszenie jest już zamknięte"}
     * ```
     * Thrown when the ticket is closed.
     *
     * @param CreateTicketMessageMessage $createTicketMessageMessage
     *
     * @return TicketTimelineItemMessage the new entry, see
     *         {@see \Polkurier\Webservice\Dto\Response\Ticket\TicketTimelineItemMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function createTicketMessage(
        CreateTicketMessageMessage $createTicketMessageMessage
    ): TicketTimelineItemMessage;

    /**
     * Changes a ticket's status.
     *
     * Only CLOSED is accepted. Closing adds an ACTION entry and a closing message from Polkurier to
     * the timeline.
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
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Nie znaleziono zgłoszenia"}
     * ```
     * ```json
     * {"status": "error", "status_code": 403, "error_code": "FORBIDDEN", "response": "Zgłoszenie jest już zamknięte"}
     * ```
     * Thrown when the ticket is already closed, or, with `"Nie można zamknąć zgłoszenia"`, when the
     * customer can't close it.
     *
     * @param SetTicketStatusMessage $setTicketStatusMessage
     *
     * @return TicketMessage payload:
     *         {@see \Polkurier\Webservice\Dto\Response\Ticket\TicketMessage}
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
     * An attachment is identified by its timeline entry id and its filename. Take both values from
     * the timeline getTicketMessages() returns.
     *
     * Attachments are stored under a new unique name, given by the timeline's `files[].name`. The
     * download needs that name; the upload name gives NOT_FOUND.
     *
     * Endpoint: `get_ticket_message_file`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_ticket_message_file",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"message_id": "…", "filename": "zdjecie_1b4e28ba-2fa1-41d2-883f-0016d3cca427.jpg"}
     * }
     * ```
     *
     * Response, with the base64 payload abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "name": "zdjecie_1b4e28ba-2fa1-41d2-883f-0016d3cca427.jpg",
     *         "type": "image/jpeg",
     *         "size": 102400,
     *         "date_created": "2026-09-23T09:10:13+02:00",
     *         "hash": "9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08",
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
     *         {@see \Polkurier\Webservice\Dto\Response\Ticket\TicketFileContentMessage}
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
     * The definition lists the fields to render. It also has a form id and a nonce, which you must
     * send back with the submission. Both change on every fetch. Depending on the topic, a
     * submission may open a ticket on the account.
     *
     * The category is a topic id from getContactTopics(). A topic without a form, or an unknown
     * one, answers `{"has_form": false}`. A website-only form answers `has_form` and `web_only`
     * true and its page in `href`, with no fields, form id or nonce.
     *
     * Endpoint: `get_contact_form`
     *
     * Request:
     * ```json
     * {
     *     "apimethod": "get_contact_form",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {"category": "fc8db0b4-acad-4f15-b2af-a066629bc002"}
     * }
     * ```
     *
     * Response, abridged:
     * ```json
     * {
     *     "status": "success",
     *     "response": {
     *         "id": "16209c1e-2605-4da5-b011-eff0b940d2c7",
     *         "fields": [
     *             {"id": "fdd0631c-04ff-4cfc-9e07-2bca219f4722", "type": "TEXT", "name": "name",
     *              "value": "", "label": "Imię"},
     *             {"id": "39d63653-a2c3-4da2-be72-404b43fa2b50", "type": "TEL", "name": "phone",
     *              "pattern": "[0-9 +\\-]*", "value": "", "label": "Telefon"}
     *         ],
     *         "properties": [],
     *         "state": [],
     *         "nonce": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9…",
     *         "has_form": true
     *     }
     * }
     * ```
     *
     * Response for a form that is on the website only:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"has_form": true, "web_only": true, "href": "https://www.polkurier.pl/kontakt/605790c7…"}
     * }
     * ```
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 15, "error_code": "PARAMS_IS_EMPTY",
     *  "response": "Brak wymaganego parametru lub jest pusty: [category]"}
     * ```
     *
     * @param GetContactFormMessage $getContactFormMessage
     *
     * @return ContactFormMessage the form, see
     *         {@see \Polkurier\Webservice\Dto\Response\Contact\ContactFormMessage}
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function getContactForm(GetContactFormMessage $getContactFormMessage): ContactFormMessage;

    /**
     * Submits a completed contact form.
     *
     * The form id and nonce come from the getContactForm() response, and change on every fetch. A
     * nonce works once, within an hour of the fetch.
     *
     * Endpoint: `save_contact_form`
     *
     * Request. The answers go in the message's `fields`, keyed by the field names in the form
     * definition, and are sent next to the category, form id and nonce:
     * ```json
     * {
     *     "apimethod": "save_contact_form",
     *     "authorization": {"login": "12345", "token": "…"},
     *     "data": {
     *         "category": "fc8db0b4-acad-4f15-b2af-a066629bc002",
     *         "form_id": "…",
     *         "nonce": "…",
     *         "name": "Jan Kowalski",
     *         "phone": "500600700",
     *         "rules_accept": true,
     *         "marketing_consent": true
     *     }
     * }
     * ```
     *
     * Response:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"status": "SUCCESS", "action": "SHOW_MESSAGE", "message": "Twoja wiadomość została wysłana. …",
     *                  "data": {"ticket_number": "202609-000123"}}
     * }
     * ```
     *
     * A form that needs the code sent by e-mail answers with its own status set to `ERROR`, inside a
     * successful envelope, so the SDK returns it rather than throwing:
     * ```json
     * {
     *     "status": "success",
     *     "response": {"status": "ERROR", "action": "UPDATE_FORM", "nonce": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9…",
     *                  "data": {"require_auth_code": true}}
     * }
     * ```
     * Show the form's auth code field and submit again with the new nonce.
     *
     * A rejected submission comes with the status in capitals, `"status": "ERROR"`, which the SDK
     * reads like any other error. Its `validation_errors` name each answer to fix. It spends the
     * nonce, and carries a fresh one in `nonce`, read with
     * {@see \Polkurier\Webservice\Dto\Response\ErrorMessage::$nonce}.
     *
     * Common errors:
     * ```json
     * {"status": "error", "status_code": 30, "error_code": "FORM_EXPIRED", "response": "Formularz wygasł"}
     * ```
     * Thrown when the nonce has expired, was already used or belongs to another form id. Fetch the
     * form again and resubmit.
     * ```json
     * {"status": "error", "status_code": 404, "error_code": "NOT_FOUND", "response": "Formularz nie istnieje"}
     * ```
     * Unknown category, no form, or a website-only form.
     * ```json
     * {"status": "ERROR", "status_code": 26, "error_code": "VALIDATION_ERROR",
     *  "response": "Należy wyrazić zgodę [marketing_consent]",
     *  "validation_errors": {"marketing_consent": ["Należy wyrazić zgodę"]}, "nonce": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9…"}
     * ```
     *
     * @param SaveContactFormMessage $saveContactFormMessage
     *
     * @return ContactFormResultMessage
     * @throws AuthorizationException
     * @throws ConnectionException
     * @throws InvalidArgumentException
     * @throws MalformedResponseException
     * @throws ValidationException
     * @throws WebserviceException
     */
    public function saveContactForm(SaveContactFormMessage $saveContactFormMessage): ContactFormResultMessage;
}
