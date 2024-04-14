<?php

namespace VercelBlobPhp\Exception;

use Exception;

class BlobUnknownException extends Exception
{
    public function __construct()
    {
        parent::__construct("This store does not exist.");
    }
}
