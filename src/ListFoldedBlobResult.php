<?php

namespace VercelBlobPhp;

final class ListFoldedBlobResult extends ListBlobResult
{
    /**
     * @param string[] $folders
     */
    public function __construct(
        array $blobs,
        ?string $cursor,
        bool $hasMore,
        public readonly array $folders,
    ) {
        parent::__construct($blobs, $cursor, $hasMore);
    }
}
