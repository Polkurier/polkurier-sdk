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
use Polkurier\Webservice\Validator\Rule\Each;
use Polkurier\Webservice\Validator\Rule\IgnoredWhen;
use Polkurier\Webservice\Validator\Rule\Valid;
use Polkurier\Webservice\Validator\Rule\ValidatedBy;

/**
 * Runs the rules a message declares as attributes.
 *
 * Keeping the rules on the fields they describe means a field and its constraints are read, changed
 * and reviewed together, and a field added without them is visibly missing them. The wire names in
 * the report come from the serializer attribute already on each property, so a renamed field cannot
 * drift away from the rule that mentions it.
 *
 * Six kinds of declaration are honoured:
 *
 * - A {@see ValueValidatorInterface} on a property: the format rules.
 * - A {@see MessageRuleInterface} on the class: the rules about how fields relate. They go on the
 *   class because that is the only scope that sees both fields — a property attribute naming a
 *   sibling would tie two fields together from a place that can see one of them.
 * - {@see Valid} on a property: descend into a nested object, or into each object in a list.
 * - {@see Each} on a property: apply one rule to every entry of a list of plain values.
 * - {@see IgnoredWhen} on the class: skip the lot, because the block does not apply.
 * - {@see ValidatedBy} on the class: run a validator written as a class, for the rules with
 *   enough logic behind them not to fit an attribute.
 *
 * The two class-level kinds are inherited, so a base message can declare a rule that governs every
 * message built on it.
 *
 * What cannot be said declaratively belongs in an {@see AbstractMessageValidator} instead, and the
 * registry runs both. Rules that depend on the role a value is playing are the usual case — an
 * address needs an email as a sender and not as a recipient, which is a fact about the order, not
 * about the address.
 *
 * Reflection is done once per class and cached. Nothing here is final or private: to drop a rule
 * the SDK applies and your integration does not want, extend this and filter what
 * {@see static::rulesFor()} returns, then hand the result to the client as its validator. Filter
 * what the parent gives back rather than replacing it — the cache holds the unfiltered reflection,
 * which is what lets two differently configured validators coexist in one process.
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
     * Gathers everything the message's own attributes object to.
     *
     * Exposed so that a hand-written validator can apply the declared rules and then add its own,
     * and so nested structures can be folded into one report.
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
            $this->collectNested($fields->value($property), $fields->field($property), $violations);
        }

        foreach ($rules['message'] as $rule) {
            $rule->validate($fields, $violations);
        }

        foreach ($rules['validators'] as $validator) {
            static::runDeclared($validator, $message, $violations);
        }
    }

    /**
     * Runs a validator a message named with {@see ValidatedBy}, folding its report into this one.
     *
     * The validator reports by throwing, which is what it does when used on its own. Here its
     * findings have to join whatever the attributes already objected to, so the exception is
     * unpacked rather than allowed to escape — otherwise a payload with a bad postcode and an
     * incoherent combination would report only the second.
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
     * Descends into a nested object, or into every entry of a list.
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
        $message = [];
        $ignored = [];
        $validators = [];

        $reflection = MessageFields::reflect($class);

        // Class-level rules are inherited: a base message declaring one governs its subclasses.
        for ($declaring = $reflection; $declaring !== false; $declaring = $declaring->getParentClass()) {
            foreach ($declaring->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();

                if ($instance instanceof MessageRuleInterface) {
                    $message[] = $instance;
                } elseif ($instance instanceof IgnoredWhen) {
                    $ignored[] = $instance;
                } elseif ($instance instanceof ValidatedBy) {
                    $validators[] = $instance->validator;
                }
            }
        }

        $current = $reflection;
        $seen = [];
        while ($current !== false) {
            foreach ($current->getProperties() as $property) {
                $name = $property->getName();

                // A property redeclared down the hierarchy is described by the first declaration
                // reached; the parent's copy is the same field.
                if (isset($seen[$name])) {
                    continue;
                }
                $seen[$name] = true;

                foreach ($property->getAttributes() as $attribute) {
                    $instance = $attribute->newInstance();

                    if ($instance instanceof ValueValidatorInterface) {
                        $values[$name][] = $instance;
                    } elseif ($instance instanceof Each) {
                        $each[$name][] = $instance->rule();
                    } elseif ($instance instanceof Valid && !\in_array($name, $nested, true)) {
                        $nested[] = $name;
                    }
                }
            }
            $current = $current->getParentClass();
        }

        return self::$rules[$class] = [
            'values' => $values,
            'each' => $each,
            'nested' => $nested,
            'message' => $message,
            'ignored' => $ignored,
            'validators' => \array_values(\array_unique($validators)),
        ];
    }
}
