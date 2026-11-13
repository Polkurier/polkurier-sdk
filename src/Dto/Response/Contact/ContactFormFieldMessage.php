<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * Licensed under the MIT License, see LICENSE.md
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Dto\Response\Contact;

use Polkurier\Webservice\Api\MessageInterface;
use Polkurier\Webservice\Enum\Contact\ContactFormFieldTypeEnum;
use Polkurier\Webservice\Enum\Contact\ContactFormSeverityEnum;
use Polkurier\Webservice\Serializer\Attribute\NamedValues;
use Polkurier\Webservice\Serializer\Attribute\OmitWhenEmpty;
use Symfony\Component\Serializer\Attribute as Serializer;

/**
 * One field of a contact form: an input to answer, or an element shown on the form, such as a
 * heading or a notice.
 *
 * Submit each answer under the field's name. Radio buttons come as one field each, sharing a name,
 * and each submits its own value. A field with a condition is shown only when the condition holds,
 * and its answer is needed only then.
 *
 * @api
 */
final readonly class ContactFormFieldMessage implements MessageInterface
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
    /**
     * Not a wire field: the API sends a choices reference in `options`, and the SDK reads it into
     * {@see self::$optionsSource}.
     */
    public const string FIELD_OPTIONS_SOURCE = 'options_source';
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
     * @param ContactFormFieldTypeEnum|null $type what the field is, null when the SDK doesn't know the type
     * @param string $name the name to submit the answer under, empty for a field that takes no answer
     * @param string $value the starting value. For a checkbox or a radio button, the value it submits when chosen
     * @param string $label display text, in Polish. It may contain HTML
     * @param string $labelAfter text shown after the input, e.g. a unit
     * @param string $description help text shown with the field
     * @param string $placeholder hint shown in an empty input
     * @param string $pattern regular expression the answer must match, empty when there is none
     * @param int|null $maxLength the longest answer, in characters. For files, the most files
     * @param int|null $maxFileSize the largest file, in bytes
     * @param int|null $maxImageFileSize the largest image file, in bytes
     * @param string[] $allowedFileMimeTypes media types a file may have
     * @param string[] $allowedFileExtensions extensions a file name may have
     * @param ContactFormOptionMessage[] $options the choices of a drop-down field
     * @param string $optionsSource the form property a drop-down field takes its choices from
     * @param string $condition when the field is shown, as the form states it. Empty when it always is
     * @param string $disabled when the field is disabled, as the form states it. Empty when it never is
     * @param string $group the group of checkboxes the field belongs to
     * @param string $groupParent the group whose checkboxes this one ticks or clears
     * @param ContactFormSeverityEnum|null $severity how a notice should be shown, null for other fields
     *        and for a severity the SDK doesn't know
     * @param string $href where a button leads
     * @param ContactFormValueMessage[] $props display properties
     */
    public function __construct(
        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_ID)]
        public string $id = '',

        /**
         * @var ContactFormFieldTypeEnum|null null when the SDK doesn't know the type
         */
        #[Serializer\SerializedName(self::FIELD_TYPE)]
        #[OmitWhenEmpty]
        public ?ContactFormFieldTypeEnum $type = null,

        /**
         * @var string the name to submit the answer under, empty for a field that takes no answer
         */
        #[Serializer\SerializedName(self::FIELD_NAME)]
        public string $name = '',

        /**
         * @var string the starting value. For a checkbox or a radio button, the value it submits when chosen
         */
        #[Serializer\SerializedName(self::FIELD_VALUE)]
        public string $value = '',

        /**
         * @var string display text, in Polish. It may contain HTML
         */
        #[Serializer\SerializedName(self::FIELD_LABEL)]
        public string $label = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_LABEL_AFTER)]
        public string $labelAfter = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DESCRIPTION)]
        public string $description = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PLACEHOLDER)]
        public string $placeholder = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_PATTERN)]
        public string $pattern = '',

        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_MAX_LENGTH)]
        #[OmitWhenEmpty]
        public ?int $maxLength = null,

        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_MAX_FILE_SIZE)]
        #[OmitWhenEmpty]
        public ?int $maxFileSize = null,

        /**
         * @var int|null
         */
        #[Serializer\SerializedName(self::FIELD_MAX_IMAGE_FILE_SIZE)]
        #[OmitWhenEmpty]
        public ?int $maxImageFileSize = null,

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ALLOWED_FILE_MIME_TYPES)]
        public array $allowedFileMimeTypes = [],

        /**
         * @var string[]
         */
        #[Serializer\SerializedName(self::FIELD_ALLOWED_FILE_EXTENSIONS)]
        public array $allowedFileExtensions = [],

        /**
         * The choices of a drop-down field. A field that takes them from the form's properties has
         * none, and names the property in {@see self::$optionsSource}.
         *
         * @var ContactFormOptionMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_OPTIONS)]
        public array $options = [],

        /**
         * The form property a drop-down field takes its choices from, as the form gives it, e.g.
         * `@COURIERS`.
         *
         * @var string empty when the field lists its choices or has none
         */
        #[Serializer\SerializedName(self::FIELD_OPTIONS_SOURCE)]
        public string $optionsSource = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_CONDITION)]
        public string $condition = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_DISABLED)]
        public string $disabled = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_GROUP)]
        public string $group = '',

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_GROUP_PARENT)]
        public string $groupParent = '',

        /**
         * @var ContactFormSeverityEnum|null
         */
        #[Serializer\SerializedName(self::FIELD_SEVERITY)]
        #[OmitWhenEmpty]
        public ?ContactFormSeverityEnum $severity = null,

        /**
         * @var string
         */
        #[Serializer\SerializedName(self::FIELD_HREF)]
        public string $href = '',

        /**
         * @var ContactFormValueMessage[]
         */
        #[Serializer\SerializedName(self::FIELD_PROPS)]
        #[NamedValues(name: ContactFormValueMessage::FIELD_NAME, value: ContactFormValueMessage::FIELD_VALUE)]
        public array $props = [],
    ) {}
}
