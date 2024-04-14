<?php

namespace VercelBlobPhp;

readonly class PutBlobResult
{
    public function __construct(
        public string $url,
        public string $downloadUrl,
        public string $pathname,
        public ?string $contentType,
        public string $contentDisposition,
    ) {
    }
}
