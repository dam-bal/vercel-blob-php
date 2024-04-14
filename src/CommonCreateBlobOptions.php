<?php

namespace VercelBlobPhp;

readonly class CommonCreateBlobOptions
{
    public function __construct(
        public string $access = 'public',
        public ?bool $addRandomSuffix = false,
        public ?string $contentType = null,
        public ?int $cacheControlMaxAge = null,
    ) {
    }
}
