<?php

namespace Utopia;

class RouterTrie
{
    protected array $children = [];
    protected ?Route $route = null;
    protected string $segment = '';
    protected ?string $matchedPattern = null;

    /**
     * @param string $matchedPattern The matched path pattern for this route
     */
    public function insert(array $segments, Route $route, string $matchedPattern): void
    {

        $node = $this;

        foreach ($segments as $segment) {
            $key = str_starts_with($segment, ':') ? ':' : $segment;

            if (!isset($node->children[$key])) {
                $node->children[$key] = new self();
                $node->children[$key]->segment = $segment;
            }

            $node = $node->children[$key];
        }

        $node->route = $route;
        $node->matchedPattern = $matchedPattern;
    }

    /**
     * @return array{route:Route|null,pattern:string|null}
     */
    public function match(array $segments): array
    {
        $result = $this->matchRecursive($segments, count($segments), 0);
        return [
            'route' => $result['route'],
            'pattern' => $result['pattern']
        ];
    }

    /**
     * @return array{route:Route|null,pattern:string|null}
     */
    private function matchRecursive(array $segments, int $segmentsCount, int $index): array
    {
        if ($index >= $segmentsCount) {
            return [
                'route' => $this->route,
                'pattern' => $this->matchedPattern
            ];
        }

        $segment = $segments[$index];

        if (isset($this->children[$segment])) {
            $result = $this->children[$segment]->matchRecursive($segments, $segmentsCount, $index + 1);
            if ($result['route'] !== null) {
                return $result;
            }
        }

        if (isset($this->children[':'])) {
            $result = $this->children[':']->matchRecursive($segments, $segmentsCount, $index + 1);
            if ($result['route'] !== null) {
                return $result;
            }
        }

        return ['route' => null, 'pattern' => null];
    }

    /**
     * Get trie statistics for debugging
     *
     * @return array Statistics about the trie structure
     */
    public function getStats(): array
    {
        $nodes = 0;
        $maxDepth = 0;
        $routes = 0;

        $this->collectStats($nodes, $maxDepth, $routes, 0);

        return [
            'total_nodes' => $nodes,
            'max_depth' => $maxDepth,
            'total_routes' => $routes,
        ];
    }

    /**
     * Collect statistics recursively
     */
    private function collectStats(int &$nodes, int &$maxDepth, int &$routes, int $currentDepth): void
    {
        $nodes++;
        $maxDepth = max($maxDepth, $currentDepth);

        if ($this->route !== null) {
            $routes++;
        }

        foreach ($this->children as $child) {
            $child->collectStats($nodes, $maxDepth, $routes, $currentDepth + 1);
        }
    }
}
