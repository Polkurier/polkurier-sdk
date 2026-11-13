<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Request\Common;

/**
 * Identifies a record to delete by its id.
 *
 * Address-book entries are identified by their role too, so use
 * {@see \Polkurier\Webservice\Dto\Request\Address\DeleteMessage} for those.
 */
final readonly class DeleteMessage extends IdMessage
{
}
