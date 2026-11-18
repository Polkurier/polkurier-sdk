<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Nullable;
use phpDocumentor\Reflection\Types\Object_;
use Polkurier\Webservice\Log\Attribute\PersonalData;
use Polkurier\Webservice\Log\Attribute\Sensitive;
use Polkurier\Webservice\Serializer\Attribute\InlineFields;
use Polkurier\Webservice\Serializer\ClassMemberReader;
use Polkurier\Webservice\Serializer\WireNameReader;

/**
 * Reads what a class marks each field it writes as, and which class a field's value holds.
 *
 * Both answers are keyed by wire name, which is how a logged body is keyed, and both are read off
 * the class that declares the field, so a key means what the class around it says it means:
 * {@see Sensitive} on {@see \Polkurier\Webservice\Dto\Response\TwoFactorToken::$id} marks that
 * `id` and no other. Every declaration of a field is read, so a mark on a private property governs
 * a field its getter also writes, and a mark a subclass does not repeat still governs.
 *
 * A key a class writes under a second spelling is answered for the spelling its
 * `#[SerializedName]` gives and no other.
 *
 * The fields of a class marked {@see InlineFields} are written into the enclosing object, so they
 * are merged into the enclosing class's maps at the same level. A key the enclosing class writes
 * itself keeps its own entry, as it does on the wire.
 *
 * Reflection and docblocks cost more than the answer is worth on every request, and a class does
 * not change while the process runs, so each class is read once and kept.
 */
final class FieldMarkReader
{
    /**
     * The attributes read off a field. Each redactor acts on one of them.
     *
     * @var list<class-string>
     */
    private const array MARKS = [Sensitive::class, PersonalData::class];

    /**
     * What each class marks its fields as, read once.
     *
     * @var array<string, array<string, list<class-string>>>
     */
    private static array $marks = [];

    /**
     * The class each class's fields hold, read once.
     *
     * @var array<string, array<string, class-string>>
     */
    private static array $classes = [];

    /**
     * The namespace and imports each class's docblocks are read in, resolved once per class rather
     * than per field, since resolving reads and tokenizes the file the class is declared in.
     *
     * @var array<string, Context>
     */
    private static array $contexts = [];

    /**
     * The marks each class puts on the keys it does not write itself, read once.
     *
     * @var array<string, list<class-string>>
     */
    private static array $foreign = [];

    /**
     * The marks on each field the class writes, by wire name.
     *
     * A field the class writes with no mark has an empty entry, which is what tells it apart from
     * a key the class does not write at all. That difference decides whether
     * {@see self::readMarksOfForeignKeys()} applies.
     *
     * @param string $class
     *
     * @return array<string, list<class-string>>
     */
    public static function readMarks(string $class): array
    {
        if (!isset(self::$marks[$class])) {
            self::read($class);
        }

        return self::$marks[$class];
    }

    /**
     * The class each field the class writes holds, by wire name: the field's own type, or the
     * element type of a collection.
     *
     * A field that holds no modelled object has no entry.
     *
     * @param string $class
     *
     * @return array<string, class-string>
     */
    public static function readClasses(string $class): array
    {
        if (!isset(self::$classes[$class])) {
            self::read($class);
        }

        return self::$classes[$class];
    }

    /**
     * The marks that govern a key the class does not write itself.
     *
     * A class that inlines a map writes keys it cannot name: a contact form's answers are named by
     * the form definition, a carrier's additional fields by the carrier. A mark on the field
     * holding that map governs all of them, since there is no property to put it on.
     *
     * @param string $class
     *
     * @return list<class-string> empty when the class writes no such map, or leaves it unmarked
     */
    public static function readMarksOfForeignKeys(string $class): array
    {
        if (!isset(self::$foreign[$class])) {
            self::read($class);
        }

        return self::$foreign[$class];
    }

    /**
     * Fills every map for a class, since one walk of its members answers them all.
     *
     * @param string $class
     *
     * @return void
     */
    private static function read(string $class): void
    {
        // Set before the inlined classes are read, so a class that inlines its own type ends the
        // walk rather than recurring into it.
        self::$marks[$class] = [];
        self::$classes[$class] = [];
        self::$foreign[$class] = [];

        $marks = [];
        $classes = [];
        $inlined = [];
        $foreign = [];
        foreach (ClassMemberReader::readAll($class) as $member) {
            if (WireNameReader::readDeclared($member) === null && !ClassMemberReader::writesAField($member)) {
                continue;
            }
            if ($member->getAttributes(InlineFields::class) !== []) {
                // The field writes no key of its own, so a mark on it cannot name one. Where it
                // inlines a record, the record's own fields carry the marks. Where it inlines a
                // map, the keys are the API's or a form definition's and cannot be listed, so a
                // mark on the holder governs every key arriving beside the ones this class writes.
                $held = self::readClassHeldBy($member);
                if ($held !== null) {
                    $inlined[] = $held;
                    continue;
                }
                $foreign = \array_values(\array_unique([...$foreign, ...self::readMarksOn($member)]));
                continue;
            }

            $wire = WireNameReader::read($member);
            $found = self::readMarksOn($member);
            $marks[$wire] = \array_values(\array_unique([...($marks[$wire] ?? []), ...$found]));
            // The most derived declaration comes first and is the one that holds the value.
            $held = self::readClassHeldBy($member);
            if ($held !== null && !isset($classes[$wire])) {
                $classes[$wire] = $held;
            }
        }

        // A key the enclosing class writes itself keeps its own entry, as it does on the wire.
        foreach ($inlined as $held) {
            $marks += self::readMarks($held);
            $classes += self::readClasses($held);
        }

        self::$marks[$class] = $marks;
        self::$classes[$class] = $classes;
        self::$foreign[$class] = $foreign;
    }

    /**
     * @param \ReflectionProperty|\ReflectionMethod $member
     *
     * @return list<class-string>
     */
    private static function readMarksOn(\ReflectionProperty|\ReflectionMethod $member): array
    {
        $found = [];
        foreach (self::MARKS as $mark) {
            if ($member->getAttributes($mark) !== []) {
                $found[] = $mark;
            }
        }

        return $found;
    }

    /**
     * The class a member's value holds: the class it is typed as, or, for a collection, the class
     * its elements are.
     *
     * An interface is not one: what a field typed as one holds is decided by the caller that fills
     * it, not by the declaration.
     *
     * @param \ReflectionProperty|\ReflectionMethod $member
     *
     * @return class-string|null
     */
    private static function readClassHeldBy(\ReflectionProperty|\ReflectionMethod $member): ?string
    {
        $declared = $member instanceof \ReflectionMethod ? $member->getReturnType() : $member->getType();
        foreach ($declared instanceof \ReflectionUnionType ? $declared->getTypes() : [$declared] as $part) {
            if ($part instanceof \ReflectionNamedType && \class_exists($part->getName())) {
                return $part->getName();
            }
        }

        return self::readElementClass($member);
    }

    /**
     * The class the elements of a collection are, from the member's `@var Foo[]` or
     * `@var array<string, Foo>` docblock, which is where the serializer reads the element type
     * from as well.
     *
     * @param \ReflectionProperty|\ReflectionMethod $member
     *
     * @return class-string|null
     */
    private static function readElementClass(\ReflectionProperty|\ReflectionMethod $member): ?string
    {
        $block = $member->getDocComment();
        $tag = $member instanceof \ReflectionMethod ? 'return' : 'var';
        if ($block === false || \preg_match('/@' . $tag . '\s+(\S+)/', $block, $matched) !== 1) {
            return null;
        }

        try {
            $type = (new TypeResolver())->resolve(
                $matched[1],
                self::readContext($member->getDeclaringClass()),
            );
        } catch (\InvalidArgumentException | \RuntimeException) {
            // A type the resolver cannot read says nothing about the field, and a body is logged
            // whether or not a docblock parses.
            return null;
        }

        foreach (self::splitType($type) as $part) {
            if (!$part instanceof Array_) {
                continue;
            }
            foreach (self::splitType($part->getValueType()) as $element) {
                $name = $element instanceof Object_ ? \ltrim((string)$element->getFqsen(), '\\') : '';
                if ($name !== '' && \class_exists($name)) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * The members of a union or a nullable type, or the type itself.
     *
     * @param Type $type
     *
     * @return list<Type>
     */
    private static function splitType(Type $type): array
    {
        if ($type instanceof Nullable) {
            return [$type->getActualType()];
        }
        if (!$type instanceof Compound) {
            return [$type];
        }

        $parts = [];
        foreach ($type as $part) {
            $parts[] = $part;
        }

        return $parts;
    }

    /**
     * @param \ReflectionClass<object> $class
     *
     * @return Context
     */
    private static function readContext(\ReflectionClass $class): Context
    {
        return self::$contexts[$class->getName()] ??= (new ContextFactory())->createFromReflector($class);
    }
}
