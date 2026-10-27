<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Contact;

/**
 * Whether a submitted contact form was accepted.
 */
enum ContactFormResultStatusEnum: string
{
    /**
     * Accepted.
     */
    case Success = 'SUCCESS';

    /**
     * Not accepted yet; the action says what the form needs.
     */
    case Error = 'ERROR';
}
