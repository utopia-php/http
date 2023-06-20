<?php

namespace Utopia;

class Transaction
{
    protected string $id;
    protected array $resourcesCallbacks;

    public function __construct()
    {
        $this->id = $this->unique();
        $this->resourcesCallbacks = [];
    }

    public function setResource(string $name, callable $callback, array $injections = []): void
    {
        $this->resourcesCallbacks[$name] = ['callback' => $callback, 'injections' => $injections];
    }

    public function getResource(string $name): mixed
    {
        if (! \array_key_exists($name, $this->resourcesCallbacks)) {
            throw new Exception('Failed to find resource: "'.$name.'"');
        }

        return \call_user_func_array(
            $this->resourcesCallbacks[$name]['callback'],
            $this->getResources($this->resourcesCallbacks[$name]['injections'])
        );
    }

    public function getResources(array $list): array
    {
        $resources = [];

        foreach ($list as $name) {
            $resources[$name] = $this->getResource($name);
        }

        return $resources;
    }

    protected static function unique(int $padding = 7): string
    {
        $uniqid = \uniqid();

        if ($padding > 0) {
            $bytes = \random_bytes(\ceil($padding / 2)); // one byte expands to two chars
            $uniqid .= \substr(\bin2hex($bytes), 0, $padding);
        }

        return $uniqid;
    }
}