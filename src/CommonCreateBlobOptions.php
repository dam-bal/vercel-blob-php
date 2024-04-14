<?php

namespace VercelBlobPhp;

class CommonCreateBlobOptions
{
    public function __construct(
        public readonly string $access = 'public',
        public readonly ?bool $addRandomSuffix = false,
        public readonly ?string $contentType = null,
        public readonly ?int $cacheControlMaxAge = null,
    ) {
    }
}
