<?php

namespace VercelBlobPhp\Exception;

use Exception;

class BlobAccessException extends Exception
{
    public function __construct()
    {
        parent::__construct("Access denied, please provide a valid token for this resource.");
    }
}
