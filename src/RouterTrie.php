<?php

namespace Utopia;

class RouterTrie
{
    protected ?Route $route = null;

    /** @var array<string,array{label:array<int,string>,node:RouterTrie}>|null */
    protected ?array $edges = null;
    protected ?string $matchedPattern = null;

    private static function token(string $segment): string
    {
        return str_starts_with($segment, ':') ? ':' : $segment;
    }

    /**
     * @param string $matchedPattern The matched path pattern for this route
     */
    public function insert(array $segments, Route $route, string $matchedPattern): void
    {
        $tokens = array_map([self::class, 'token'], $segments);
        $node = $this;
        $index = 0;
        $count = count($tokens);

        while ($index < $count) {
            $key = $tokens[$index];

            if ($node->edges === null || !isset($node->edges[$key])) {
                $child = new self();
                $node->edges ??= [];
                $node->edges[$key] = [
                    'label' => array_slice($tokens, $index),
                    'node' => $child,
                ];
                $node = $child;
                $index = $count;
                break;
            }

            $edge = $node->edges[$key];
            $label = $edge['label'];
            $remaining = array_slice($tokens, $index);
            $max = min(count($label), count($remaining));

            $common = 0;
            while ($common < $max && $label[$common] === $remaining[$common]) {
                $common++;
            }

            if ($common === count($label)) {
                $node = $edge['node'];
                $index += $common;
                continue;
            }

            $splitNode = new self();
            $prefix = array_slice($label, 0, $common);
            $oldRemainder = array_slice($label, $common);

            $splitNode->edges = [
                $oldRemainder[0] => [
                    'label' => $oldRemainder,
                    'node' => $edge['node'],
                ],
            ];

            $node->edges[$key] = [
                'label' => $prefix,
                'node' => $splitNode,
            ];

            $newRemainder = array_slice($remaining, $common);
            if (empty($newRemainder)) {
                $splitNode->route = $route;
                $splitNode->matchedPattern = $matchedPattern;
                return;
            }

            $newChild = new self();
            $splitNode->edges[$newRemainder[0]] = [
                'label' => $newRemainder,
                'node' => $newChild,
            ];
            $node = $newChild;
            $index = $count;
            break;
        }

        $node->route = $route;
        $node->matchedPattern = $matchedPattern;
    }

    /**
     * @return array{route:Route|null,pattern:string|null}
     */
    public function match(array $segments): array
    {
        $result = $this->matchRecursive($segments, 0, count($segments));
        return [
            'route' => $result['route'],
            'pattern' => $result['pattern']
        ];
    }

    /**
     * @return array{route:Route|null,pattern:string|null}
     */
    private function matchRecursive(array $segments, int $index, int $segmentsCount): array
    {
        if ($index >= $segmentsCount) {
            return [
                'route' => $this->route,
                'pattern' => $this->matchedPattern
            ];
        }

        $segment = $segments[$index];

        $result = $this->matchEdge($segment, $segments, $index, $segmentsCount);
        if ($result['route'] !== null) {
            return $result;
        }

        $result = $this->matchEdge(':', $segments, $index, $segmentsCount);
        if ($result['route'] !== null) {
            return $result;
        }

        return ['route' => null, 'pattern' => null];
    }

    /**
     * @return array{route:Route|null,pattern:string|null}
     */
    private function matchEdge(string $key, array $segments, int $index, int $segmentsCount): array
    {
        if ($this->edges === null || !isset($this->edges[$key])) {
            return ['route' => null, 'pattern' => null];
        }

        $edge = $this->edges[$key];
        $label = $edge['label'];
        $labelCount = count($label);

        if ($index + $labelCount > $segmentsCount) {
            return ['route' => null, 'pattern' => null];
        }

        for ($i = 0; $i < $labelCount; $i++) {
            $token = $label[$i];
            if ($token === ':') {
                continue;
            }
            if ($token !== $segments[$index + $i]) {
                return ['route' => null, 'pattern' => null];
            }
        }

        return $edge['node']->matchRecursive($segments, $index + $labelCount, $segmentsCount);
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

        if ($this->edges !== null) {
            foreach ($this->edges as $edge) {
                $edgeDepth = $currentDepth + count($edge['label']);
                $edge['node']->collectStats($nodes, $maxDepth, $routes, $edgeDepth);
            }
        }
    }
}
