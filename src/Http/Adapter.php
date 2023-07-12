<?php

namespace Utopia\Http;

abstract class Adapter
{
    protected Files $files;

    public function __construct()
    {
        $this->files = new Files();
    }

    abstract public function onRequest(callable $callback);
    abstract public function start();

    /**
     * Load directory.
     *
     * @param  string  $directory
     * @param  string|null  $root
     * @return void
     *
     * @throws \Exception
    */
    public function loadFiles(string $directory, string $root = null): void
    {
        $this->files->load($directory, $root);
    }

    /**
     * Is file loaded.
     *
     * @param  string  $uri
     * @return bool
     */
    public function isFileLoaded(string $uri): bool
    {
        return $this->files->isFileLoaded($uri);
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
        return $this->files->getFileContents($uri);
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
        return $this->files->getFileMimeType($uri);
    }
}
