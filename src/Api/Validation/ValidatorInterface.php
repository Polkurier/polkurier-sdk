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
 * The SDK's default implementation is the {@see \Polkurier\Webservice\Validator\ValidatorRegistry}
 * built by {@see \Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory}, which
 * {@see \Polkurier\Webservice\PolkurierWebservice} uses unless you pass your own validator.
 * Implement this interface to replace it or to check your own rules before a request is sent.
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
