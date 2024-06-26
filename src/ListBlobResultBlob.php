<?php

namespace VercelBlobPhp;

use DateTime;

class ListBlobResultBlob
{
    public function __construct(
        public readonly string $url,
        public readonly string $downloadUrl,
        public readonly string $pathname,
        public readonly int $size,
        public readonly DateTime $uploadedAt,
    ) {
    }
}