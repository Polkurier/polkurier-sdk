<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Dto\Request\Order\GetOrdersMessage;
use Polkurier\Webservice\Dto\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\AttributeValidator;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;
use Polkurier\Webservice\Validator\Rule as Validate;
use Polkurier\Webservice\Validator\Rule\ValidatedBy;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * A message is checked by what it declares, and by nothing it does not.
 *
 * Each message names its validators, so the pairing is written once. A validator attached to the
 * wrong message would stop running without a sound. These cover that wiring.
 */
#[CoversClass(ValidatedBy::class)]
#[CoversClass(AttributeValidator::class)]
final class ValidatorWiringTest extends TestCase
{
    /**
     * The registry knows about the attribute engine and nothing else, yet the class validators
     * still run — which is the whole point of declaring them on the message.
     */
    public function testAValidatorTheRegistryHasNeverHeardOfStillRuns(): void
    {
        $this->expectException(ValidationException::class);

        DefaultValidatorRegistryFactory::create()
            ->validate(new RegisterPaymentMessage(mode: PaymentModeEnum::Prepaid));
    }

    /**
     * A registry keyed on the payload could only ever reach the top-level message. Declaring the
     * validator on the class means a payload nested inside another one keeps its rules.
     */
    public function testANestedMessageIsCheckedByItsOwnDeclaredValidator(): void
    {
        $outer = new WiringOuterMessage(new WiringInnerMessage('')); // the inner value is invalid

        try {
            (new AttributeValidator())->validate($outer);
            self::fail('the nested message should have been rejected');
        } catch (ValidationException $e) {
            self::assertSame(
                ['inner.value' => ['must not be empty when the nested validator is reached']],
                $e->getValidationErrors(),
            );
        }
    }

    /**
     * The rules a class declares are cached once per message class, so what one validator reads
     * must not decide what another sees. A subclass validates differently by overriding
     * `collect()`; the cache is out of its reach.
     *
     * @return void
     */
    public function testASubclassCannotChangeWhatTheStockValidatorSees(): void
    {
        $message = new GetOrdersMessage(
            dateFrom: new \DateTimeImmutable('2026-02-01'),
            dateTo: new \DateTimeImmutable('2026-01-01'),
        );

        $count = static function (AttributeValidator $validator) use ($message): int {
            $pool = new ViolationPool();
            $validator->collect($message, $pool);

            return \count($pool->getViolations());
        };

        self::assertSame(1, $count(new WiringPlainValidator()), 'the reversed range is reported');
        self::assertSame(1, $count(new AttributeValidator()), 'and still reported afterwards');
    }

    public function testAMessageDeclaringNothingPasses(): void
    {
        (new AttributeValidator())->validate(new WiringInnerMessage('anything'));

        $this->expectNotToPerformAssertions();
    }

    /**
     * Only the SDK's own attributes are instantiated. Another library's attribute on a message is
     * left alone, even one that can't be built without its own context.
     */
    public function testAForeignAttributeOnAMessageIsNeverInstantiated(): void
    {
        try {
            (new AttributeValidator())->validate(new WiringForeignAttributeMessage(''));
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame(['name'], \array_keys($e->getValidationErrors()));
        }
    }
}

/**
 * Rejects an empty value, so that reaching it is observable.
 *
 * @extends AbstractMessageValidator<WiringInnerMessage>
 */
final class WiringInnerValidator extends AbstractMessageValidator
{
    /**
     * @param MessageInterface $data
     * @param ViolationPool $violations
     *
     * @return void
     */
    protected function collect(MessageInterface $data, ViolationPool $violations): void
    {
        /** @var WiringInnerMessage $data */
        if ($data->value === '') {
            $violations->add('value', 'must not be empty when the nested validator is reached');
        }
    }
}

/**
 * A message that names a validator and is only ever reached through another message.
 */
#[ValidatedBy(WiringInnerValidator::class)]
readonly class WiringInnerMessage implements MessageInterface
{
    /**
     * @param string $value
     */
    public function __construct(
        public string $value,
    ) {}
}

/**
 * Declares no rules of its own beyond descending into the one it holds.
 */
readonly class WiringOuterMessage implements MessageInterface
{
    /**
     * @param WiringInnerMessage $inner
     */
    public function __construct(
        public WiringInnerMessage $inner,
    ) {}
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class WiringExplodingAttribute
{
    public function __construct()
    {
        throw new \LogicException('A foreign attribute must not be instantiated by the validator.');
    }
}

final readonly class WiringForeignAttributeMessage implements MessageInterface
{
    public function __construct(
        #[WiringExplodingAttribute]
        #[Validate\NotBlank]
        public string $name,
    ) {}
}

/**
 * A subclass of the kind the class docblock invites, carrying nothing of its own.
 */
final class WiringPlainValidator extends AttributeValidator
{
}
