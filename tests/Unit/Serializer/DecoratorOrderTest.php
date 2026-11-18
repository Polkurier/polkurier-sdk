<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Serializer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormFieldMessage;
use Polkurier\Webservice\Dto\Response\Contact\ContactFormValueMessage;
use Polkurier\Webservice\Serializer\ResponseQuirksDenormalizer;
use Polkurier\Webservice\Serializer\SerializerProvider;
use Polkurier\Webservice\Serializer\WireFormatDenormalizer;

/**
 * The decorators that rewrite a payload run outside the one that builds the object from it, so
 * what the object normalizer type-checks is already in the shape the property declares.
 */
#[CoversClass(SerializerProvider::class)]
#[CoversClass(ResponseQuirksDenormalizer::class)]
#[CoversClass(WireFormatDenormalizer::class)]
final class DecoratorOrderTest extends TestCase
{
    /**
     * A contact form field carries both rewrites: `options` holding a choices reference belongs to
     * another property than the one it arrives under, and `props` arrives keyed by name where the
     * property holds pairs. Read without a drift collector, either rewrite arriving too late makes
     * the object normalizer reject the value.
     *
     * @return void
     */
    public function testAFieldIsRewrittenBeforeItsValuesAreTypeChecked(): void
    {
        $field = (new SerializerProvider())->getSerializer()->denormalize([
            ContactFormFieldMessage::FIELD_NAME => 'target',
            ContactFormFieldMessage::FIELD_OPTIONS => '@COURIERS',
            ContactFormFieldMessage::FIELD_PROPS => ['rows' => 4, 'hint' => 'Numer przesyłki'],
        ], ContactFormFieldMessage::class, 'json');

        self::assertInstanceOf(ContactFormFieldMessage::class, $field);
        self::assertSame([], $field->options);
        self::assertSame('@COURIERS', $field->optionsSource);
        self::assertEquals([
            new ContactFormValueMessage('rows', 4),
            new ContactFormValueMessage('hint', 'Numer przesyłki'),
        ], $field->props);
    }
}
