<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Contact\ContactFormFieldTypeEnum;
use Polkurier\Webservice\Enum\Contact\ContactFormSeverityEnum;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One field of a contact form: an input to answer, or an element shown on the form, such as a
 * heading or a notice.
 *
 * Submit each answer under the field's name. Radio buttons come as one field each, sharing a name,
 * and each submits its own value. A field with a condition is shown only when the condition holds,
 * and its answer is needed only then.
 */
readonly class ContactFormFieldMessage implements MessageInterface
{
    /**+@#
     * JSON parameter keys
     */
    public const string FIELD_ID = 'id';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_NAME = 'name';
    public const string FIELD_VALUE = 'value';
    public const string FIELD_LABEL = 'label';
    public const string FIELD_LABEL_AFTER = 'labelAfter';
    public const string FIELD_DESCRIPTION = 'description';
    public const string FIELD_PLACEHOLDER = 'placeholder';
    public const string FIELD_PATTERN = 'pattern';
    public const string FIELD_MAX_LENGTH = 'maxLength';
    public const string FIELD_MAX_FILE_SIZE = 'maxFileSize';
    public const string FIELD_MAX_IMAGE_FILE_SIZE = 'maxImageFileSize';
    public const string FIELD_ALLOWED_FILE_MIME_TYPES = 'allowedFileMimeTypes';
    public const string FIELD_ALLOWED_FILE_EXTENSIONS = 'allowedFileExtensions';
    public const string FIELD_OPTIONS = 'options';
    public const string FIELD_CONDITION = 'condition';
    public const string FIELD_DISABLED = 'disabled';
    public const string FIELD_GROUP = 'group';
    public const string FIELD_GROUP_PARENT = 'groupParent';
    public const string FIELD_SEVERITY = 'severity';
    public const string FIELD_HREF = 'href';
    public const string FIELD_PROPS = 'props';
    /**-@#*/

    /**
     * @param string $id the field's id on this form
     * @param ?ContactFormFieldTypeEnum $type what the field is, null when the SDK doesn't know the type
     * @param string $name the name to submit the answer under, empty for a field that takes no answer
     * @param string $value the starting value. For a checkbox or a radio button, the value it submits when chosen
     * @param string $label display text, in Polish. It may contain HTML
     * @param string $labelAfter text shown after the input, e.g. a unit
     * @param string $description help text shown with the field
     * @param string $placeholder hint shown in an empty input
     * @param string $pattern regular expression the answer must match, empty when there is none
     * @param ?int $maxLength the longest answer, in characters. For files, the most files
     * @param ?int $maxFileSize the largest file, in bytes
     * @param ?int $maxImageFileSize the largest image file, in bytes
     * @param string[] $allowedFileMimeTypes media types a file may have
     * @param string[] $allowedFileExtensions extensions a file name may have
     * @param ContactFormOptionMessage[]|string|null $options the choices of a drop-down field
     * @param string $condition when the field is shown, as the form states it. Empty when it always is
     * @param string $disabled when the field is disabled, as the form states it. Empty when it never is
     * @param string $group the group of checkboxes the field belongs to
     * @param string $groupParent the group whose checkboxes this one ticks or clears
     * @param ?ContactFormSeverityEnum $severity how a notice should be shown, null for other fields
     *        and for a severity the SDK doesn't know
     * @param string $href where a button leads
     * @param array<string, mixed> $props display properties
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        private string $id = '',

        /**
         * @var ?ContactFormFieldTypeEnum
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        #[OmitWhenEmpty]
        private ?ContactFormFieldTypeEnum $type = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        private string $name = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        private string $value = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_LABEL)]
        private string $label = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_LABEL_AFTER)]
        private string $labelAfter = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        private string $description = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PLACEHOLDER)]
        private string $placeholder = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PATTERN)]
        private string $pattern = '',

        /**
         * @var ?int
         */
        #[Serializer\SerializedName(self::FIELD_MAX_LENGTH)]
        #[OmitWhenEmpty]
        private ?int $maxLength = null,

        /**
         * @var ?int
         */
        #[Serializer\SerializedName(self::FIELD_MAX_FILE_SIZE)]
        #[OmitWhenEmpty]
        private ?int $maxFileSize = null,

        /**
         * @var ?int
         */
        #[Serializer\SerializedName(self::FIELD_MAX_IMAGE_FILE_SIZE)]
        #[OmitWhenEmpty]
        private ?int $maxImageFileSize = null,

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ALLOWED_FILE_MIME_TYPES)]
        private array $allowedFileMimeTypes = [],

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ALLOWED_FILE_EXTENSIONS)]
        private array $allowedFileExtensions = [],

        /**
         * @var ContactFormOptionMessage[]|string|null
         */
        #[Serializer\SerializedName(self::FIELD_OPTIONS)]
        #[OmitWhenEmpty]
        private array|string|null $options = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONDITION)]
        private string $condition = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DISABLED)]
        private string $disabled = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_GROUP)]
        private string $group = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_GROUP_PARENT)]
        private string $groupParent = '',

        /**
         * @var ?ContactFormSeverityEnum
         */
        #[Serializer\SerializedName(self::FIELD_SEVERITY)]
        #[OmitWhenEmpty]
        private ?ContactFormSeverityEnum $severity = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_HREF)]
        private string $href = '',

        /**
         * @var array<string, mixed>
         */
        #[Serializer\SerializedName(self::FIELD_PROPS)]
        private array $props = [],
    ) {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return ?ContactFormFieldTypeEnum null when the SDK doesn't know the type
     */
    public function getType(): ?ContactFormFieldTypeEnum
    {
        return $this->type;
    }

    /**
     * @return string the name to submit the answer under, empty for a field that takes no answer
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string the starting value. For a checkbox or a radio button, the value it submits when chosen
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string display text, in Polish. It may contain HTML
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getLabelAfter(): string
    {
        return $this->labelAfter;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return ?int
     */
    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /**
     * @return ?int
     */
    public function getMaxFileSize(): ?int
    {
        return $this->maxFileSize;
    }

    /**
     * @return ?int
     */
    public function getMaxImageFileSize(): ?int
    {
        return $this->maxImageFileSize;
    }

    /**
     * @return string[]
     */
    public function getAllowedFileMimeTypes(): array
    {
        return $this->allowedFileMimeTypes;
    }

    /**
     * @return string[]
     */
    public function getAllowedFileExtensions(): array
    {
        return $this->allowedFileExtensions;
    }

    /**
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }

    /**
     * @return string
     */
    public function getDisabled(): string
    {
        return $this->disabled;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @return string
     */
    public function getGroupParent(): string
    {
        return $this->groupParent;
    }

    /**
     * @return ?ContactFormSeverityEnum
     */
    public function getSeverity(): ?ContactFormSeverityEnum
    {
        return $this->severity;
    }

    /**
     * @return string
     */
    public function getHref(): string
    {
        return $this->href;
    }

    /**
     * The choices of a drop-down field.
     *
     * Some fields name a list of the form's properties instead, see {@see self::getOptionsSource()}.
     *
     * @return ContactFormOptionMessage[]
     */
    public function getOptions(): array
    {
        return \is_array($this->options) ? $this->options : [];
    }

    /**
     * The form property a drop-down field takes its choices from, when it doesn't list them.
     *
     * @return string the reference as the form gives it, e.g. `@COURIERS`, empty when the field
     *         lists its choices or has none
     */
    public function getOptionsSource(): string
    {
        return \is_string($this->options) ? $this->options : '';
    }

    /**
     * Display properties, e.g. a font size or an icon.
     *
     * @return list<ContactFormValueMessage>
     */
    public function getProps(): array
    {
        return ContactFormValueMessage::listOf($this->props);
    }
}
