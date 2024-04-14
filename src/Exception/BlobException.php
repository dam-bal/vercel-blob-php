<?php

namespace VercelBlobPhp\Exception;

use Exception;

class BlobException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct(sprintf('Vercel Blob: %s', $message));
    }
}
