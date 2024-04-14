<?php

namespace VercelBlobPhp\Exception;

use Exception;

class BlobServiceNotAvailableException extends Exception
{
    public function __construct()
    {
        parent::__construct("The blob service is currently not available. Please try again.");
    }
}
