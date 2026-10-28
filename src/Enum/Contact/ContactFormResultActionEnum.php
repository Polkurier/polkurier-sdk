<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Contact;

/**
 * What to do with a submitted contact form's result.
 */
enum ContactFormResultActionEnum: string
{
    /**
     * Show the message in place of the form.
     */
    case ShowMessage = 'SHOW_MESSAGE';

    /**
     * Show the message in a popup, e.g. a payment the ticket waits for.
     */
    case ShowPopup = 'SHOW_POPUP';

    /**
     * Show the form again with the fields the result asks for, and submit it with the new nonce.
     */
    case UpdateForm = 'UPDATE_FORM';
}
