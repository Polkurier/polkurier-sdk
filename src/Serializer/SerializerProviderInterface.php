<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Supplies the serializer the request and response factories map with.
 *
 * {@see SerializerProvider} is the implementation, and is final: the mapping is what every call
 * depends on, and a half-changed one fails in ways that look like the API misbehaving. Most of
 * what an application wants to vary is reachable through {@see SerializerConfigInterface} without
 * going near it.
 *
 * This exists for the cases that are not — a different normalizer stack, a metadata source that is
 * not attributes, an instance shared with the rest of an application's serialization. Implement
 * it and pass the result to the client.
 */
interface SerializerProviderInterface
{
    /**
     * The request side serializes to JSON; the response side denormalizes an already-decoded body,
     * so both contracts are promised.
     *
     * @return SerializerInterface&DenormalizerInterface
     */
    public function getSerializer(): SerializerInterface&DenormalizerInterface;
}
