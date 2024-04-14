<?php

namespace VercelBlobPhp\Exception;

use Exception;

class BlobStoreNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct("This store has been suspended.");
    }
}
