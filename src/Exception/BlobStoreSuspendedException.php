<?php

namespace VercelBlobPhp\Exception;

use Exception;

class BlobStoreSuspendedException extends Exception
{
    public function __construct()
    {
        parent::__construct("This store does not exist.");
    }
}
