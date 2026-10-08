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
 * Supplies the serializer used by the request and response factories.
 *
 * {@see \Polkurier\Webservice\Serializer\SerializerProvider} is the default, and a subclass of it
 * can change how the serializer is built. Most settings are in
 * {@see \Polkurier\Webservice\Serializer\SerializerConfigInterface}. Every call depends on the
 * mapping, and a broken one fails in ways that look like API errors.
 *
 * Implement this interface if you need something else entirely, e.g. metadata from somewhere other
 * than attributes, or a serializer shared with the rest of your application. Then pass it to the
 * client.
 */
interface SerializerProviderInterface
{
    /**
     * Requests are serialized to JSON and responses are denormalized from a decoded body, so the
     * serializer has to implement both interfaces.
     *
     * @return SerializerInterface&DenormalizerInterface
     */
    public function getSerializer(): SerializerInterface&DenormalizerInterface;
}
