<?php

namespace Utopia\Validator;

use Utopia\Validator;
use Utopia\Validator\URL\Pattern;

/**
 * URL
 *
 * Validate that an variable is a valid URL
 *
 * @package Appwrite\Network\Validator
 */
class URL extends Validator
{
    /**
     * @var array<Pattern>
     */
    protected array $patterns;

    /**
     * @param array<Pattern> $patterns
     */
    public function __construct(array $patterns = [])
    {
        $this->patterns = $patterns;
    }

    /**
     * Get Description
     *
     * Returns validator description
     *
     * @return string
     */
    public function getDescription(): string
    {
        if (!empty($this->patterns)) {
            return 'Value must be a valid URL matching one of the following patterns (' . \implode(', ', $this->patterns) . ')';
        }

        return 'Value must be a valid URL';
    }

    /**
     * Is valid
     *
     * Validation will pass when $value is valid URL.
     *
     * @param  mixed $value
     * @return bool
     */
    public function isValid($value): bool
    {
        $parsed = $this->parseUrl($value);
        if (!$parsed) {
            return false;
        }

        if (empty($this->patterns)) {
            return true;
        }

        foreach ($this->patterns as $pattern) {
            $schemeMatch = empty($pattern->schemes) || in_array($parsed['scheme'], $pattern->schemes);
            $hostMatch = empty($pattern->hosts) || in_array($parsed['host'], $pattern->hosts);

            if ($schemeMatch && $hostMatch) {
                return true;
            }
        }

        return false;
    }

    protected function parseUrl($value): mixed
    {
        $parsed = \parse_url($value);

        // `parse_url` returns false if the URL is invalid, and when hostname is missing.
        // In this case, try to extract the scheme using regex.
        if (!$parsed && $matches = \preg_match('/^([a-z]+):\/\//', $value, $matches)) {
            $parsed = ['scheme' => $matches[1] ?? ''];
        }

        return $parsed;
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
}
