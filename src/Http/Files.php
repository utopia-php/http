<?php

namespace Utopia\Http;

use Exception;

class Files
{
    /**
     * @var array<string, mixed>
     */
    protected array $loaded = [];

    /**
     * @var int
     */
    protected int $count = 0;

    /**
     * @var array<string, mixed>
     */
    protected array $mimeTypes = [];

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
    public function addMimeType(string $mimeType): void
    {
        $this->mimeTypes[$mimeType] = true;
    }

    /**
     * Remove MIME type.
     *
     * @param  string  $mimeType
     * @return void
     */
    public function removeMimeType(string $mimeType): void
    {
        if (isset($this->mimeTypes[$mimeType])) {
            unset($this->mimeTypes[$mimeType]);
        }
    }

    /**
     * Get MimeType List
     *
     * @return array<string, mixed>
     */
    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
    }

    /**
     * Get Files Loaded Count
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
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
    public function load(string $directory, string $root = null): void
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
                $this->load($dirPath, strval($root));

                continue;
            }

            $key = substr($dirPath, strlen(strval($root)));

            if (array_key_exists($key, $this->loaded)) {
                continue;
            }

            $this->loaded[$key] = [
                'contents' => file_get_contents($dirPath),
                'mimeType' => (array_key_exists($extension, self::EXTENSIONS))
                    ? self::EXTENSIONS[$extension]
                    : mime_content_type($dirPath),
            ];

            $this->count++;
        }

        closedir($handle);
    }

    /**
     * Is file loaded.
     *
     * @param  string  $uri
     * @return bool
     */
    public function isFileLoaded(string $uri): bool
    {
        if (! array_key_exists($uri, $this->loaded)) {
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
    public function getFileContents(string $uri): mixed
    {
        if (! array_key_exists($uri, $this->loaded)) {
            throw new Exception('File not found or not loaded: '.$uri);
        }

        return $this->loaded[$uri]['contents'];
    }

    /**
     * Get file MIME type.
     *
     * @param  string  $uri
     * @return string
     *
     * @throws \Exception
     */
    public function getFileMimeType(string $uri): mixed
    {
        if (! array_key_exists($uri, $this->loaded)) {
            throw new Exception('File not found or not loaded: '.$uri);
        }

        return $this->loaded[$uri]['mimeType'];
    }

    /**
     * Reset.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->count = 0;
        $this->loaded = [];
        $this->mimeTypes = [];
    }
}
