<?php

namespace Utopia;

class Hook {

    /**
     * Description
     *
     * @var string
     */
    protected string $desc = '';

    /**
     * Group
     *
     * @var array
     */
    protected array $groups = [];

    /**
     * Action Callback
     *
     * @var callable
     */
    protected $action;

    /**
     * Injections
     *
     * List of route required injections for action callback
     *
     * @var array
     */
    protected array $injections = [];

    /**
     * Add Description
     *
     * @param string $desc
     * @return static
     */
    public function desc(string $desc): static
    {
        $this->desc = $desc;

        return $this;
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDesc(): string
    {
        return $this->desc;
    }

    /**
     * Add Group
     *
     * @param array $groups
     * @return static
     */
    public function groups(array $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * Get Groups
     *
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Add Action
     *
     * @param callable $action
     * @return static
     */
    public function action(callable $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Get Action
     *
     * @return callable
     */
    public function getAction()
    {
        return $this->action;
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
     * Inject
     *
     * @param string $injection
     *
     * @throws Exception
     *
     * @return static
     */
    public function inject(string $injection): static
    {
        if (array_key_exists($injection, $this->injections)) {
            throw new Exception('Injection already declared for ' . $injection);
        }

        $this->injections[] = $injection;

        return $this;
    }
}