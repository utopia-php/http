<?php

namespace Utopia;

use Exception;

class Files
{
    /**
     * @var array<string, mixed>
     */
    protected static array $loaded = [];

    /**
     * @var int
     */
    protected static int $count = 0;

    /**
     * @var array<string, mixed>
     */
    protected static array $mimeTypes = [];

    /**
     * @var array<string, mixed>
     */
    public const EXTENSIONS = [
        'css' => 'text/css',
        'js' => 'text/javascript',
        'svg' => 'image/svg+xml',
    ];

    /**
     * Add MIME type.
     *
     * @param  string  $mimeType
     * @return void
     */
    public static function addMimeType(string $mimeType): void
    {
        self::$mimeTypes[$mimeType] = true;
    }

    /**
     * Remove MIME type.
     *
     * @param  string  $mimeType
     * @return void
     */
    public static function removeMimeType(string $mimeType): void
    {
        if (isset(self::$mimeTypes[$mimeType])) {
            unset(self::$mimeTypes[$mimeType]);
        }
    }

    /**
     * Get MimeType List
     *
     * @return array<string, mixed>
     */
    public static function getMimeTypes(): array
    {
        return self::$mimeTypes;
    }

    /**
     * Get Files Loaded Count
     *
     * @return int
     */
    public static function getCount(): int
    {
        return self::$count;
    }

    /**
     * Load directory.
     *
     * @param  string  $directory
     * @param  string|null  $root
     * @return void
     *
     * @throws \Exception
     */
    public static function load(string $directory, string $root = null): void
    {
        if (! is_readable($directory)) {
            throw new Exception("Failed to load directory: {$directory}");
        }

        $directory = realpath($directory);

        $root ??= $directory;

        $handle = opendir(strval($directory));

        while ($path = readdir($handle)) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);

            if (in_array($path, ['.', '..'])) {
                continue;
            }

            if (in_array($extension, ['php', 'phtml'])) {
                continue;
            }

            if (substr($path, 0, 1) === '.') {
                continue;
            }

            $dirPath = $directory.'/'.$path;

            if (is_dir($dirPath)) {
                self::load($dirPath, strval($root));

                continue;
            }

            $key = substr($dirPath, strlen(strval($root)));

            if (array_key_exists($key, self::$loaded)) {
                continue;
            }

            self::$loaded[$key] = [
                'contents' => file_get_contents($dirPath),
                'mimeType' => (array_key_exists($extension, self::EXTENSIONS))
                    ? self::EXTENSIONS[$extension]
                    : mime_content_type($dirPath),
            ];

            self::$count++;
        }

        closedir($handle);
    }

    /**
     * Is file loaded.
     *
     * @param  string  $uri
     * @return bool
     */
    public static function isFileLoaded(string $uri): bool
    {
        if (! array_key_exists($uri, self::$loaded)) {
            return false;
        }

        return true;
    }

    /**
     * Get file contents.
     *
     * @param  string  $uri
     * @return string
     *
     * @throws \Exception
     */
    public static function getFileContents(string $uri): mixed
    {
        if (! array_key_exists($uri, self::$loaded)) {
            throw new Exception('File not found or not loaded: '.$uri);
        }

        return self::$loaded[$uri]['contents'];
    }

    /**
     * Get file MIME type.
     *
     * @param  string  $uri
     * @return string
     *
     * @throws \Exception
     */
    public static function getFileMimeType(string $uri): mixed
    {
        if (! array_key_exists($uri, self::$loaded)) {
            throw new Exception('File not found or not loaded: '.$uri);
        }

        return self::$loaded[$uri]['mimeType'];
    }

    /**
     * Reset.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$count = 0;
        self::$loaded = [];
        self::$mimeTypes = [];
    }
}
