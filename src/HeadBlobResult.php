<?php

namespace VercelBlobPhp;

use DateTime;

readonly class HeadBlobResult
{
    public function __construct(
        public string $url,
        public string $downloadUrl,
        public int $size,
        public DateTime $uploadedAt,
        public string $pathname,
        public string $contentType,
        public string $contentDisposition,
        public string $cacheControl,
    ) {
    }
}
