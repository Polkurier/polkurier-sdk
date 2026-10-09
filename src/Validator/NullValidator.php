<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;

/**
 * A validator that accepts everything.
 *
 * Used when payload validation is switched off, so the service layer has one code path whether or
 * not it is enabled.
 */
class NullValidator implements ValidatorInterface
{
    /**
     * @param MessageInterface $data
     *
     * @return void
     */
    public function validate(MessageInterface $data): void
    {
    }
}
