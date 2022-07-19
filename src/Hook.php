<?php

namespace Utopia;

class Hook {
    protected $callback;
    protected array $injections;

    public function __construct(callable $callback, array $injections = [])
    {
        $this->callback = $callback;
        $this->injections = $injections;
    }


    /**
     * Get Callback
     *
     * @return callable
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }

    /**
     * Set Callaback
     *
     * @param callable $callback
     * @return self
     */
    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get Injections
     *
     * @return array
     */
    public function getInjections(): array
    {
        return $this->injections;
    }

    /**
     * Set Injections
     *
     * @param array $injections
     * @return self
     */
    public function setInjections(array $injections): self
    {
        $this->injections = $injections;

        return $this;
    }
}