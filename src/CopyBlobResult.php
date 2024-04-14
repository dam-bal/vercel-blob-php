<?php

namespace VercelBlobPhp;

class CopyBlobResult
{
    public function __construct(
        public readonly string $url,
        public readonly string $downloadUrl,
        public readonly string $pathname,
        public readonly ?string $contentType,
        public readonly string $contentDisposition,
    ) {
    }
}
