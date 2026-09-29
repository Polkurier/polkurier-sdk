<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Builder\Contact;

use Polkurier\Webservice\Builder\AbstractBuilder;
use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Request\Contact\SaveContactFormMessage;

/**
 * Assembles a contact form submission.
 */
class SaveContactFormMessageBuilder extends AbstractBuilder
{
    /**
     * @param string $category required before build()
     *
     * @return static
     */
    public function category(string $category): static
    {
        return $this->set('category', $category);
    }

    /**
     * @param string $formId required before build()
     *
     * @return static
     */
    public function formId(string $formId): static
    {
        return $this->set('formId', $formId);
    }

    /**
     * @param string $nonce required before build()
     *
     * @return static
     */
    public function nonce(string $nonce): static
    {
        return $this->set('nonce', $nonce);
    }

    /**
     * @param array<string, scalar> $fields the answers, keyed by the form's field names
     *
     * @return static
     */
    public function fields(array $fields): static
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
