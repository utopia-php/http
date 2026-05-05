<?php

namespace Utopia\Http;

/**
 * Configuration for HTTP compression.
 */
final readonly class Compression
{
    public const int COMPRESSION_BROTLI_LEVEL_DEFAULT = 4;
    public const int COMPRESSION_ZSTD_LEVEL_DEFAULT = 3;

    private const int DEFAULT_MIN_SIZE_BYTES = 1024;
    private const array DEFAULT_ALGORITHMS = [];

    /**
     * Mime Types with compression support
     *
     * @var array<string, bool>
     */
    private const array DEFAULT_MIME_TYPES  = [
        // Text
        'text/html' => true,
        'text/richtext' => true,
        'text/plain' => true,
        'text/css' => true,
        'text/x-script' => true,
        'text/x-component' => true,
        'text/x-java-source' => true,
        'text/x-markdown' => true,

        // JavaScript
        'application/javascript' => true,
        'application/x-javascript' => true,
        'text/javascript' => true,
        'text/js' => true,

        // Icons
        'image/x-icon' => true,
        'image/vnd.microsoft.icon' => true,

        // Scripts
        'application/x-perl' => true,
        'application/x-httpd-cgi' => true,

        // XML and JSON
        'text/xml' => true,
        'application/xml' => true,
        'application/rss+xml' => true,
        'application/vnd.api+json' => true,
        'application/x-protobuf' => true,
        'application/json' => true,
        'application/manifest+json' => true,
        'application/ld+json' => true,
        'application/graphql+json' => true,
        'application/geo+json' => true,

        // Multipart
        'multipart/bag' => true,
        'multipart/mixed' => true,

        // XHTML
        'application/xhtml+xml' => true,

        // Fonts
        'font/ttf' => true,
        'font/otf' => true,
        'font/x-woff' => true,
        'image/svg+xml' => true,
        'application/vnd.ms-fontobject' => true,
        'application/ttf' => true,
        'application/x-ttf' => true,
        'application/otf' => true,
        'application/x-otf' => true,
        'application/truetype' => true,
        'application/opentype' => true,
        'application/x-opentype' => true,
        'application/font-woff' => true,
        'application/eot' => true,
        'application/font' => true,
        'application/font-sfnt' => true,

        // WebAssembly
        'application/wasm' => true,
        'application/javascript-binast' => true,
    ];

    /**
     * @param int $minSizeBytes The minimum size in bytes for compression to be applied
     * @param mixed $algorithms The algorithms to use for compression
     */
    public function __construct(
        private int $minSizeBytes = self::DEFAULT_MIN_SIZE_BYTES,
        public mixed $algorithms = self::DEFAULT_ALGORITHMS,
        public int $brotliLevel = self::COMPRESSION_BROTLI_LEVEL_DEFAULT,
        public int $zstdLevel = self::COMPRESSION_ZSTD_LEVEL_DEFAULT,
        private array $mimeTypes = self::DEFAULT_MIME_TYPES
    ) {
    }

    public function isCompressible(Response $request): bool
    {
        $hasAcceptEncoding = !empty($request->getHeader('accept-encoding', ''));

        $isCompressibleMimeType = isset($this->mimeTypes[$request->getHeader('content-type', '')]);
        $isGreaterThanMinSize = \strlen($request->getRawPayload()) > $this->minSizeBytes;
        return $isCompressibleMimeType && $isGreaterThanMinSize;
    }
}
