<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Request\Contact;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Dto\Request\Contact\SaveContactFormMessage;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Assembles a contact form submission.
 *
 * @api
 */
class SaveContactFormMessageBuilder extends AbstractBuilder
{
    /**
     * @param string $category required before build()
     *
     * @return static
     */
    public function setCategory(string $category): static
    {
        return $this->set('category', $category);
    }

    /**
     * @param string $formId required before build()
     *
     * @return static
     */
    public function setFormId(string $formId): static
    {
        return $this->set('formId', $formId);
    }

    /**
     * @param string $nonce required before build()
     *
     * @return static
     */
    public function setNonce(string $nonce): static
    {
        return $this->set('nonce', $nonce);
    }

    /**
     * @param array<string, scalar|list<\Polkurier\Webservice\Dto\Request\Ticket\TicketAttachmentMessage>> $fields
     *        the answers, keyed by the form's field names; a files field takes a list of attachments
     *
     * @return static
     */
    public function setFields(array $fields): static
    {
        return $this->set('fields', $fields);
    }

    /**
     * @return SaveContactFormMessage
     * @throws InvalidArgumentException
     */
    public function build(): SaveContactFormMessage
    {
        return $this->make(SaveContactFormMessage::class);
    }
}
