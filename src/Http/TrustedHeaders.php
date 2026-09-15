<?php

declare(strict_types=1);

namespace Utopia\Http;

/**
 * Which forwarded headers this server believes.
 *
 * A header is only worth reading where the hop in front of this server
 * overwrites it; anywhere else the client chooses its own answer. Each list is
 * read in order and the first usable value wins.
 */
final readonly class TrustedHeaders
{
    public const string FORWARDED_PROTO = 'x-forwarded-proto';

    public const string REPLACED_PATH = 'x-replaced-path';

    /**
     * @var array<int, string>
     */
    public array $ip;

    /**
     * @var array<int, string>
     */
    public array $proto;

    /**
     * @var array<int, string>
     */
    public array $path;

    /**
     * @param  array<int, string>  $ip  Headers naming the client address.
     * @param  array<int, string>  $proto  Headers naming the scheme the client used.
     * @param  array<int, string>  $path  Headers naming the path a proxy rewrote.
     */
    public function __construct(array $ip = [], array $proto = [self::FORWARDED_PROTO], array $path = [self::REPLACED_PATH])
    {
        $this->ip = $this->normalize($ip);
        $this->proto = $this->normalize($proto);
        $this->path = $this->normalize($path);
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<int, string>
     */
    private function normalize(array $headers): array
    {
        $lowered = array_map(strtolower(...), $headers);
        $trimmed = array_map(trim(...), $lowered);

        return array_values(array_filter($trimmed));
    }
}
