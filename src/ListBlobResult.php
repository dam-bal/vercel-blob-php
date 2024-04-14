<?php

namespace VercelBlobPhp;

class ListBlobResult
{
    /**
     * @param ListBlobResultBlob[] $blobs
     */
    public function __construct(
        public readonly array $blobs,
        public readonly ?string $cursor,
        public readonly bool $hasMore,
    ) {
    }
}
