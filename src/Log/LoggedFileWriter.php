<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Log;

use Polkurier\Webservice\Enum\Http\ApiMethodInterface;

/**
 * Takes file contents out of a body before it is logged.
 *
 * Labels, protocols, invoices and attachments travel as base64, and one of them can be larger than
 * everything else in a log put together. This finds them, a `file` value or the entries of a
 * `files` map from file name to contents, and replaces each with a short note.
 *
 * When saving is on, each file is first written to its own file in a directory, and the note
 * gives its path, so the log points to it. Otherwise the note only gives the size. Saving never
 * throws: if a file can't be written, the note says so and the log entry goes ahead.
 */
class LoggedFileWriter
{
    public const string FIELD_FILE = 'file';
    public const string FIELD_FILES = 'files';

    /**
     * Matches the name of every file this class saves: the time, the method, a random part and the
     * file's own name, e.g. `20261005T101500-get_label-1a2b3c4d-file.pdf`.
     *
     * Use it to recognise saved files, e.g. to delete old ones, without touching other files in
     * the same directory. Names keep matching it in later versions.
     */
    public const string FILE_NAME_PATTERN = '/^\d{8}T\d{6}-[A-Za-z0-9_]+-[0-9a-f]{8}-[A-Za-z0-9._-]+$/';

    /**
     * Keys of a file's name next to its contents.
     */
    protected const array NAME_FIELDS = ['name', 'filename'];

    /**
     * File extensions by the first bytes of the contents.
     */
    protected const array SIGNATURES = [
        '%PDF' => 'pdf',
        "\x89PNG" => 'png',
        "\xFF\xD8\xFF" => 'jpg',
        'GIF8' => 'gif',
        'PK' => 'zip',
    ];

    /**
     * @param array<array-key, mixed> $data the decoded body
     * @param bool $save whether to write each file out, or only note its size
     * @param string $directory where files are written
     * @param ApiMethodInterface $apiMethod the method the body belongs to, used in file names
     *
     * @return array<array-key, mixed>
     */
    public function process(array $data, bool $save, string $directory, ApiMethodInterface $apiMethod): array
    {
        $name = null;
        foreach (static::NAME_FIELDS as $nameField) {
            if (\is_string($data[$nameField] ?? null) && $data[$nameField] !== '') {
                $name = $data[$nameField];
            }
        }

        foreach ($data as $key => $value) {
            if ($key === static::FIELD_FILE && \is_string($value)) {
                $data[$key] = $this->replace($value, $name, $save, $directory, $apiMethod);
            } elseif ($key === static::FIELD_FILES && \is_array($value) && $this->isFileMap($value)) {
                foreach ($value as $fileName => $contents) {
                    /** @var string $contents */
                    $value[$fileName] = $this->replace($contents, (string)$fileName, $save, $directory, $apiMethod);
                }
                $data[$key] = $value;
            } elseif (\is_array($value)) {
                $data[$key] = $this->process($value, $save, $directory, $apiMethod);
            }
        }

        return $data;
    }

    /**
     * A map from file name to contents, as opposed to a list of file descriptions.
     *
     * @param array<array-key, mixed> $value
     *
     * @return bool
     */
    protected function isFileMap(array $value): bool
    {
        if ($value === [] || \array_is_list($value)) {
            return false;
        }

        foreach ($value as $contents) {
            if (!\is_string($contents)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $base64
     * @param string|null $name
     * @param bool $save
     * @param string $directory
     * @param ApiMethodInterface $apiMethod
     *
     * @return string the note that takes the contents' place
     */
    protected function replace(
        string $base64,
        ?string $name,
        bool $save,
        string $directory,
        ApiMethodInterface $apiMethod,
    ): string {
        $contents = \base64_decode($base64, true);
        if ($contents === false || $contents === '') {
            // Not file contents after all, e.g. a file name.
            return $base64;
        }

        $size = \strlen($contents);
        if (!$save) {
            return \sprintf('[file left out of the log: %d bytes]', $size);
        }

        $path = $this->path($directory, $apiMethod, $name, $contents);
        if (!$this->write($directory, $path, $contents)) {
            return \sprintf('[file left out of the log: %d bytes, could not be saved in %s]', $size, $directory);
        }

        return \sprintf('[file saved to %s: %d bytes]', $path, $size);
    }

    /**
     * A new, unique path for the file.
     *
     * @param string $directory
     * @param ApiMethodInterface $apiMethod
     * @param string|null $name
     * @param string $contents
     *
     * @return string
     */
    protected function path(string $directory, ApiMethodInterface $apiMethod, ?string $name, string $contents): string
    {
        $base = \preg_replace('/[^A-Za-z0-9._-]+/', '_', \basename($name ?? '')) ?? '';
        $base = \trim(\substr($base, -80), '._');
        if ($base === '') {
            $base = 'file';
        }
        if (\pathinfo($base, \PATHINFO_EXTENSION) === '') {
            $base .= '.' . $this->extension($contents);
        }

        $method = \preg_replace('/[^A-Za-z0-9_]+/', '_', (string)$apiMethod->value) ?? '';

        return \rtrim($directory, '/\\') . \DIRECTORY_SEPARATOR . \sprintf(
            '%s-%s-%s-%s',
            \date('Ymd\THis'),
            $method === '' ? 'call' : $method,
            \bin2hex(\random_bytes(4)),
            $base,
        );
    }

    /**
     * @param string $contents
     *
     * @return string
     */
    protected function extension(string $contents): string
    {
        foreach (static::SIGNATURES as $signature => $extension) {
            if (\str_starts_with($contents, (string)$signature)) {
                return $extension;
            }
        }

        return 'bin';
    }

    /**
     * @param string $directory
     * @param string $path
     * @param string $contents
     *
     * @return bool
     */
    protected function write(string $directory, string $path, string $contents): bool
    {
        if (!\is_dir($directory) && !@\mkdir($directory, 0700, true) && !\is_dir($directory)) {
            return false;
        }

        return @\file_put_contents($path, $contents, \LOCK_EX) === \strlen($contents);
    }
}
