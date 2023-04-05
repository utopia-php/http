<?php

namespace Utopia;

class Hook
{
    /**
     * Description
     *
     * @var string
     */
    protected string $desc = '';

    /**
     * Parameters
     *
     * List of route params names and validators
     *
     * @var array
     */
    protected array $params = [];

    /**
     * Group
     *
     * @var array
     */
    protected array $groups = [];

    /**
     * Labels
     *
     * List of route label names
     *
     * @var array
     */
    protected array $labels = [];

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

    public function __construct()
    {
        $this->action = function (): void {
        };
    }

    /**
     * Add Description
     *
     * @param  string  $desc
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
     * @param  array  $groups
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
     * Add Label
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function label(string $key, mixed $value): static
    {
        $this->labels[$key] = $value;

        return $this;
    }

    /**
     * Get Label
     *
     * Return given label value or default value if label doesn't exists
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getLabel(string $key, mixed $default): mixed
    {
        return (isset($this->labels[$key])) ? $this->labels[$key] : $default;
    }

    /**
     * Add Action
     *
     * @param  callable  $action
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
     * @param  string  $injection
     * @return static
     *
     * @throws Exception
     */
    public function inject(string $injection): static
    {
        if (array_key_exists($injection, $this->injections)) {
            throw new Exception('Injection already declared for '.$injection);
        }

        $this->injections[$injection] = [
            'name' => $injection,
            'order' => count($this->params) + count($this->injections),
        ];

        return $this;
    }

    /**
     * Add Param
     *
     * @param  string  $key
     * @param  mixed  $default
     * @param  Validator|callable  $validator
     * @param  string  $description
     * @param  bool  $optional
     * @param  array  $injections
     * @param  bool  $skipValidation
     * @return static
     */
    public function param(string $key, mixed $default, Validator|callable $validator, string $description = '', bool $optional = false, array $injections = [], bool $skipValidation = false): static
    {
        $this->params[$key] = [
            'default' => $default,
            'validator' => $validator,
            'description' => $description,
            'optional' => $optional,
            'injections' => $injections,
            'skipValidation' => $skipValidation,
            'value' => null,
            'order' => count($this->params) + count($this->injections),
        ];

        return $this;
    }

    /**
     * Get Params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get Param Values
     *
     * @return array
     */
    public function getParamsValues(): array
    {
        $values = [];

        foreach ($this->params as $key => $param) {
            $values[$key] = $param['value'];
        }

        return $values;
    }

    /**
     * Set Param Value
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return static
     *
     * @throws Exception
     */
    public function setParamValue(string $key, mixed $value): static
    {
        if (!isset($this->params[$key])) {
            throw new Exception('Unknown key');
        }

        $this->params[$key]['value'] = $value;

        return $this;
    }

    /**
     * Get Param Value
     *
     * @param  string  $key
     * @return mixed
     *
     * @throws Exception
     */
    public function getParamValue(string $key): mixed
    {
        if (!isset($this->params[$key])) {
            throw new Exception('Unknown key');
        }

        return $this->params[$key]['value'];
    }
}
