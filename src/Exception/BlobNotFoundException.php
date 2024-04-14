<?php

namespace VercelBlobPhp\Exception;

use Exception;

class BlobNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct("The requested blob does not exist");
    }
}
