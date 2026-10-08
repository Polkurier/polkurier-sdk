<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Contact;

/**
 * How a notice on a contact form should be shown.
 */
enum ContactFormSeverityEnum: string
{
    /**
     * Something went well.
     */
    case Success = 'SUCCESS';

    /**
     * Something to be careful about.
     */
    case Warning = 'WARNING';

    /**
     * Something that stops the request.
     */
    case Danger = 'DANGER';
}
