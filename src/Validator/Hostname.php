<?php

/**
 * Utopia PHP Framework
 *
 * @package Framework
 * @subpackage Validator
 *
 * @link https://github.com/utopia-php/framework
 * @author Appwrite Team <team@appwrite.io>
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Validator;

use Exception;
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
     * @param string[] $allowList
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
        return 'Value must be a hostname without path, port and protocol.';
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
     * @param mixed $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        // Validate proper format
        if (!\is_string($value) || empty($value)) {
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
        if (!isset($this->allowList) || empty($this->allowList)) {
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
            if (\str_contains($allowedHostname, '*')) {
                // Split hostnames into sections (subdomains)
                $allowedSections = \explode('.', $allowedHostname);
                $valueSections = \explode('.', $value);

                // Only if amount of sections matches
                if (\count($allowedSections) === \count($valueSections)) {
                    $matchesAmount = 0;

                    // Loop through all sections
                    for ($sectionIndex = 0; $sectionIndex < \count($allowedSections); $sectionIndex++) {
                        $allowedSection = $allowedSections[$sectionIndex];

                        // If section matches, or wildcard symbol is used, increment match count
                        if ($allowedSection === '*' || $allowedSection === $valueSections[$sectionIndex]) {
                            $matchesAmount++;
                        } else {
                            // If one fails, the whole check always fails; we can skip iterations
                            break;
                        }
                    }

                    // If every section matched; allow
                    if ($matchesAmount === \count($allowedSections)) {
                        return true;
                    }
                }
            }
        }

        // If finished loop above without result, match is not found
        return false;
    }
}
