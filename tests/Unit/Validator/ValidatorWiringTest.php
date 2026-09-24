<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Test\Unit\Validator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Payment\PaymentModeEnum;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Request\Payment\RegisterPaymentMessage;
use Polkurier\Webservice\Validator\AbstractMessageValidator;
use Polkurier\Webservice\Validator\AttributeValidator;
use Polkurier\Webservice\Validator\DefaultValidatorRegistryFactory;
use Polkurier\Webservice\Validator\Rule as Validate;
use Polkurier\Webservice\Validator\Rule\ValidatedBy;
use Polkurier\Webservice\Validator\ViolationPool;

/**
 * A message is checked by what it declares, and by nothing it does not.
 *
 * The validators used to be paired with their messages in a registry, which meant the pairing was
 * written twice — once there, once on the validator — with nothing keeping the two in step. A
 * validator registered against the wrong message simply stopped running, silently. These cover
 * the wiring that replaced it.
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
            new AttributeValidator()->validate($outer);
            self::fail('the nested message should have been rejected');
        } catch (ValidationException $e) {
            self::assertSame(
                ['inner.value' => ['must not be empty when the nested validator is reached']],
                $e->getValidationErrors(),
            );
        }
    }

    public function testAMessageDeclaringNothingPasses(): void
    {
        new AttributeValidator()->validate(new WiringInnerMessage('anything'));

        self::expectNotToPerformAssertions();
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
    public function __construct(public string $value) {}
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
        #[Validate\Valid]
        public WiringInnerMessage $inner,
    ) {}
}
