<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Request\Common;

/**
 * Identifies a record to delete by its id.
 *
 * Address-book entries are identified by their role too, so use
 * {@see \Polkurier\Webservice\Request\Address\DeleteMessage} for those.
 */
readonly class DeleteMessage extends IdMessage
{
}
