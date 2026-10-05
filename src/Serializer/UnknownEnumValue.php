<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Serializer;

/**
 * A value in a response that the enum for its field doesn't have.
 */
readonly class UnknownEnumValue
{
    /**
     * @param class-string<\BackedEnum> $enum the enum the field uses
     * @param mixed $value the value as the API sent it
     * @param string|null $path where in the payload it was, by PHP property name, e.g. `result[0].shipmentType`
     */
    public function __construct(
        private string $enum,
        private mixed $value,
        private ?string $path = null,
    ) {}

    /**
     * @return class-string<\BackedEnum>
     */
    public function getEnum(): string
    {
        return $this->enum;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }
}
