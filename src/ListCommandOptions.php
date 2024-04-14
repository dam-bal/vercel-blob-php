<?php

namespace VercelBlobPhp;

class ListCommandOptions
{
    public function __construct(
        public readonly ?int $limit = null,
        public readonly ?string $prefix = null,
        public readonly ?string $cursor = null,
        public readonly ?ListCommandMode $mode = null,
    ) {
    }
}
