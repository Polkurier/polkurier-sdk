<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Api\Validation;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Exception\InvalidArgumentException;

/**
 * Validates a message before it is sent.
 *
 * No implementation ships with the SDK: messages check their own invariants in their constructors,
 * and everything beyond that depends on carrier and account rules the client cannot know. This is
 * the extension point for an application that wants to enforce its own rules before spending a
 * request.
 */
interface ValidatorInterface
{
    /**
     * @param MessageInterface $data
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(MessageInterface $data): void;
}
