<?php

namespace VercelBlobPhp\Exception;

use Exception;

class BlobServiceRateLimitedException extends Exception
{
    public function __construct(?int $seconds)
    {
        parent::__construct(
            "Too many requests please lower the number of concurrent requests"
            . ($seconds ? sprintf(' - try again in %s seconds', $seconds) : "")
        );
    }
}
