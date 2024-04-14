<?php

namespace VercelBlobPhp;

use DateTime;

class HeadBlobResult
{
    public function __construct(
        public readonly string $url,
        public readonly string $downloadUrl,
        public readonly int $size,
        public readonly DateTime $uploadedAt,
        public readonly string $pathname,
        public readonly string $contentType,
        public readonly string $contentDisposition,
        public readonly string $cacheControl,
    ) {
    }
}
