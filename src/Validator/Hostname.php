<?php

namespace Utopia\Validator;

use Utopia\Validator;

class Hostname extends Validator
{
    /**
     * @var string[]
     */
    protected array $allowList = [];

    /**
     * Constructor
     *
     * Sets allowed hostname patterns
     *
     * @param  string[]  $allowList
     */
    public function __construct(array $allowList = [])
    {
        $this->allowList = $allowList;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Value must be a valid hostname without path, port and protocol.';
    }

    /**
     * Is array
     *
     * Function will return true if object is array.
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return false;
    }

    /**
     * Get Type
     *
     * Returns validator type.
     *
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_STRING;
    }

    /**
     * @param  mixed  $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        // Validate proper format
        if (!\is_string($value) || empty($value)) {
            return false;
        }

        // Max length 253 chars: https://en.wikipedia.org/wiki/Hostname#:~:text=The%20entire%20hostname%2C%20including%20the,maximum%20of%20253%20ASCII%20characters
        if (\mb_strlen($value) > 253) {
            return false;
        }

        // This tests: 'http://', 'https://', and 'myapp.com/route'
        if (\str_contains($value, '/')) {
            return false;
        }

        // This tests for: 'myapp.com:3000'
        if (\str_contains($value, ':')) {
            return false;
        }

        // Logic #1: Empty allowList means everything is allowed
        if (empty($this->allowList)) {
            return true;
        }

        // Logic #2: Allow List not empty, there are rules to check
        // Loop through all allowed hostnames until match is found
        foreach ($this->allowList as $allowedHostname) {
            // If exact match; allow
            // If *, allow everything
            if ($value === $allowedHostname || $allowedHostname === '*') {
                return true;
            }

            // If wildcard symbol used
            if (\str_starts_with($allowedHostname, '*')) {
                // Remove starting * symbol before comparing
                $allowedHostname = substr($allowedHostname, 1);

                // If rest of hostname match; allow
                // Notice allowedHostname still includes starting dot. Root domain is NOT allowed by wildcard.
                if (\str_ends_with($value, $allowedHostname)) {
                    return true;
                }
            }
        }

        // If finished loop above without result, match is not found
        return false;
    }
}
