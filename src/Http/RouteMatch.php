<?php

declare(strict_types=1);

namespace Utopia\Http;

/**
 * Routing state for the current request: the matched Route, the
 * prepared-path key it matched under (placeholders → ":::"), and the
 * resolved+validated argument map. `$arguments` is empty until
 * `Http::execute()` writes it just before the route action runs;
 * available to the action and downstream shutdown / error hooks.
 *
 * Inject with `->inject('match')`.
 */
final class RouteMatch
{
    /** @var array<string, mixed> */
    public array $arguments;

    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        public readonly Route $route,
        public readonly string $path,
        array $arguments = [],
    ) {
        $this->arguments = $arguments;
    }
}
