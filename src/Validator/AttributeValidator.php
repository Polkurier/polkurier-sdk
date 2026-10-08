<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Validator;

use Polkurier\Webservice\Exception\InvalidArgumentException;
use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Api\Validation\MessageRuleInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface;
use Polkurier\Webservice\Api\Validation\ValueValidatorInterface;
use Polkurier\Webservice\Api\Validation\ValidatorInterface as MessageValidatorInterface;
use Polkurier\Webservice\Exception\ValidationException;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Validator\Rule\Each;
use Polkurier\Webservice\Validator\Rule\IgnoredWhen;
use Polkurier\Webservice\Validator\Rule\Valid;
use Polkurier\Webservice\Validator\Rule\ValidatedBy;

/**
 * Runs the rules a message declares as attributes.
 *
 * The rules sit on the fields they describe, so a field and its constraints are read and changed
 * together. Field names in the report are the wire names taken from each property's serializer
 * attribute, so renaming a field can't leave a rule pointing at the old name.
 *
 * Supported declarations:
 *
 * - A {@see ValueValidatorInterface} on a property: format rules.
 * - A {@see MessageRuleInterface} on the class: rules about how fields relate. These go on the
 *   class because only the class can see both fields.
 * - {@see Valid} on a property: validate a nested object, or each object in a list.
 * - {@see Each} on a property: apply one rule to every entry of a list of plain values.
 * - {@see IgnoredWhen} on the class: skip all rules when the block does not apply.
 * - {@see ValidatedBy} on the class: run a validator class, for rules with too much logic for an
 *   attribute.
 *
 * Class-level declarations are inherited, so a rule on a base message applies to every message
 * that extends it.
 *
 * Rules that can't be written as attributes go in an
 * {@see \Polkurier\Webservice\Validator\AbstractMessageValidator}. The usual case is a rule that
 * depends on the role a value plays. For example, an address needs an email when it is the sender
 * but not when it is the recipient.
 *
 * Reflection runs once per class and is cached. Nothing here is final or private, so a subclass can
 * drop a rule by filtering what {@see static::rulesFor()} returns. The cache holds the unfiltered
 * rules, which lets 2 differently configured validators run in the same process.
 */
class AttributeValidator implements ValidatorInterface
{
    /**
     * Rules per class, as [property => ValueValidatorInterface[]] plus the class-level rules.
     *
     * @var array<class-string, array{
     *     values: array<string, ValueValidatorInterface[]>,
     *     each: array<string, ValueValidatorInterface[]>,
     *     nested: string[],
     *     inlined: string[],
     *     message: MessageRuleInterface[],
     *     ignored: IgnoredWhen[],
     *     validators: class-string<MessageValidatorInterface>[]
     * }>
     */
    protected static array $rules = [];

    /**
     * One instance per declared validator, shared across every message that names it.
     *
     * @var array<class-string<MessageValidatorInterface>, MessageValidatorInterface>
     */
    protected static array $declared = [];

    /**
     * @param MessageInterface $data
     *
     * @return void
     * @throws ValidationException
     * @throws InvalidArgumentException
     */
    public function validate(MessageInterface $data): void
    {
        $violations = new ViolationPool();
        $this->collect($data, $violations);
        $violations->throwIfAny();
    }

    /**
     * Collects the violations found by the message's attributes.
     *
     * Public so a hand-written validator can apply the declared rules and then add its own, and so
     * nested structures end up in one report.
     *
     * @param object $message
     * @param ViolationPool $violations
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function collect(object $message, ViolationPool $violations): void
    {
        $rules = static::rulesFor($message::class);
        $fields = new MessageFields($message);

        foreach ($rules['ignored'] as $ignored) {
            if ($ignored->applies($fields)) {
                return;
            }
        }

        foreach ($rules['values'] as $property => $validators) {
            $value = $fields->value($property);

            foreach ($validators as $validator) {
                $violations->check($fields->field($property), $value, $validator);
            }
        }

        foreach ($rules['each'] as $property => $validators) {
            $value = $fields->value($property);

            if (!\is_iterable($value)) {
                continue;
            }

            foreach ($value as $index => $item) {
                $violations->check(
                    \sprintf('%s[%s]', $fields->field($property), (string)$index),
                    $item,
                    ...$validators,
                );
            }
        }

        foreach ($rules['nested'] as $property) {
            $value = $fields->value($property);
            if (\in_array($property, $rules['inlined'], true) && \is_object($value)) {
                // An inlined object's fields are sent at this level, so they are reported here too
                $this->collect($value, $violations);
                continue;
            }
            $this->collectNested($value, $fields->field($property), $violations);
        }

        foreach ($rules['message'] as $rule) {
            $rule->validate($fields, $violations);
        }

        foreach ($rules['validators'] as $validator) {
            static::runDeclared($validator, $message, $violations);
        }
    }

    /**
     * Runs a validator named with {@see ValidatedBy} and adds its violations to this report.
     *
     * The validator reports by throwing. The exception is caught and its errors added to the pool,
     * so they are reported together with the attribute violations. Otherwise a payload with a bad
     * postcode and an invalid field combination would only report the second.
     *
     * @param class-string<MessageValidatorInterface> $validator
     * @param object $message
     * @param ViolationPool $violations
     *
     * @return void
     */
    protected static function runDeclared(string $validator, object $message, ViolationPool $violations): void
    {
        if (!$message instanceof MessageInterface) {
            return;
        }

        self::$declared[$validator] ??= new $validator();

        try {
            self::$declared[$validator]->validate($message);
        } catch (ValidationException $e) {
            foreach ($e->getValidationErrors() as $field => $messages) {
                foreach ($messages as $singleMessage) {
                    $violations->add($field, $singleMessage);
                }
            }
        }
    }

    /**
     * Validates a nested object, or every object in a list.
     *
     * @param mixed $value
     * @param string $field
     * @param ViolationPool $violations
     *
     * @return void
     * @throws InvalidArgumentException
     */
    protected function collectNested(mixed $value, string $field, ViolationPool $violations): void
    {
        if (\is_object($value)) {
            $nested = new ViolationPool();
            $this->collect($value, $nested);
            $violations->mergeUnder($field, $nested);

            return;
        }

        if (!\is_iterable($value)) {
            return;
        }

        foreach ($value as $index => $item) {
            if (!\is_object($item)) {
                continue;
            }
            $nested = new ViolationPool();
            $this->collect($item, $nested);
            $violations->mergeUnder(\sprintf('%s[%s]', $field, (string)$index), $nested);
        }
    }


    /**
     * @param class-string $class
     *
     * @return array{
     *     values: array<string, ValueValidatorInterface[]>,
     *     each: array<string, ValueValidatorInterface[]>,
     *     nested: string[],
     *     inlined: string[],
     *     message: MessageRuleInterface[],
     *     ignored: IgnoredWhen[],
     *     validators: class-string<MessageValidatorInterface>[]
     * }
     * @throws InvalidArgumentException when the class does not exist
     */
    protected static function rulesFor(string $class): array
    {
        if (isset(self::$rules[$class])) {
            return self::$rules[$class];
        }

        $values = [];
        $each = [];
        $nested = [];
        $inlined = [];
        $message = [];
        $ignored = [];
        $validators = [];

        $reflection = MessageFields::reflect($class);

        // Class-level rules are inherited, so a rule on a base message applies to its subclasses.
        for ($declaring = $reflection; $declaring !== false; $declaring = $declaring->getParentClass()) {
            // Only the SDK's own attributes are instantiated, so a foreign attribute on a message
            // is never constructed here.
            $rules = $declaring->getAttributes(MessageRuleInterface::class, \ReflectionAttribute::IS_INSTANCEOF);
            foreach ($rules as $attribute) {
                $message[] = $attribute->newInstance();
            }
            foreach ($declaring->getAttributes(IgnoredWhen::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $ignored[] = $attribute->newInstance();
            }
            foreach ($declaring->getAttributes(ValidatedBy::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $validators[] = $attribute->newInstance()->validator;
            }
        }

        $current = $reflection;
        $seen = [];
        while ($current !== false) {
            foreach ($current->getProperties() as $property) {
                $name = $property->getName();

                // For a property redeclared in a subclass, the first declaration found wins. The
                // parent's copy is the same field.
                if (isset($seen[$name])) {
                    continue;
                }
                $seen[$name] = true;

                $rules = $property->getAttributes(ValueValidatorInterface::class, \ReflectionAttribute::IS_INSTANCEOF);
                foreach ($rules as $attribute) {
                    $values[$name][] = $attribute->newInstance();
                }
                foreach ($property->getAttributes(Each::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                    $each[$name][] = $attribute->newInstance()->rule();
                }
                if ($property->getAttributes(Valid::class, \ReflectionAttribute::IS_INSTANCEOF) !== []
                    && !\in_array($name, $nested, true)
                ) {
                    $nested[] = $name;
                }
                if ($property->getAttributes(InlineFields::class) !== [] && !\in_array($name, $inlined, true)) {
                    $inlined[] = $name;
                }
            }
            $current = $current->getParentClass();
        }

        return self::$rules[$class] = [
            'values' => $values,
            'each' => $each,
            'nested' => $nested,
            'inlined' => $inlined,
            'message' => $message,
            'ignored' => $ignored,
            'validators' => \array_values(\array_unique($validators)),
        ];
    }
}
