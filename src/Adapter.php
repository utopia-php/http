<?php

namespace Utopia;

abstract class Adapter
{
    protected Files $files;

    public function __construct()
    {
        $this->files = new Files();
    }
    abstract public function getRequest(): Request;
    abstract public function getResponse(): Response;
    /**
     * Load directory.
     *
     * @param  string  $directory
     * @param  string|null  $root
     * @return void
     *
     * @throws \Exception
    */
    public function loadfiles(string $diectory, string $root = null): void
    {
        $this->files->load($diectory, $root);
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
}
