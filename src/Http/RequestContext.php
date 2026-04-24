<?php

namespace Utopia\Http;

/**
 * Per-request mutable overlay for route labels and ad-hoc attributes.
 *
 * {@see Route} is frozen after registration; per-request consumers that need
 * to tag the current request (e.g. "router=true") write here instead, and
 * readers fall through to the matched Route's own labels for defaults.
 *
 * Lifetime is a single request; one instance is created per request by the
 * dispatcher and stored in the per-request DI container under `context`.
 */
final class RequestContext
{
    /** @var array<string, mixed> */
    private array $labels = [];

    public function __construct(private ?RouteMatch $match = null) {}

    public function setMatch(?RouteMatch $match): void
    {
        $this->match = $match;
    }

    public function getMatch(): ?RouteMatch
    {
        return $this->match;
    }

    public function label(string $key, mixed $value): self
    {
        $this->labels[$key] = $value;

        return $this;
    }

    public function getLabel(string $key, mixed $default = null): mixed
    {
        if (\array_key_exists($key, $this->labels)) {
            return $this->labels[$key];
        }

        return $this->match?->route->getLabel($key, $default) ?? $default;
    }
}
