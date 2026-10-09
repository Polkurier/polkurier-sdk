<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Contact;

/**
 * What a contact form field is: an input to fill in, or an element shown on the form.
 */
enum ContactFormFieldTypeEnum: string
{
    /**
     * A single line of text.
     */
    case Text = 'TEXT';

    /**
     * Several lines of text.
     */
    case Textarea = 'TEXTAREA';

    /**
     * An email address.
     */
    case Email = 'EMAIL';

    /**
     * A phone number.
     */
    case Tel = 'TEL';

    /**
     * A date.
     */
    case Date = 'DATE';

    /**
     * A checkbox. One without a name only ticks or clears the others in its group.
     */
    case Checkbox = 'CHECKBOX';

    /**
     * One choice out of the field's options, shown as radio buttons.
     */
    case Radio = 'RADIO';

    /**
     * One choice out of the field's options, shown as a drop-down list.
     */
    case Select = 'SELECT';

    /**
     * Files to attach.
     */
    case Files = 'FILES';

    /**
     * The button that submits the form.
     */
    case Submit = 'SUBMIT';

    /**
     * A link styled as a button.
     */
    case Button = 'BUTTON';

    /**
     * A notice, shown with the field's severity.
     */
    case Alert = 'ALERT';

    /**
     * A section heading.
     */
    case Heading = 'HEADING';

    /**
     * A caption.
     */
    case Label = 'LABEL';
}
