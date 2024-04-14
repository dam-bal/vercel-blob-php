<?php

use VercelBlobPhp\Client;

require __DIR__ . '/../vendor/autoload.php';

$client = new Client();

if (!empty($_GET['test-put'])) {
    $result = $client->put('this-is-test.txt', 'Testing');

    echo "<pre>";
    echo var_export($result, true);
    echo "</pre>";
}

if (!empty($_GET['test-head'])) {
    $result = $client->head($_GET['test-head']);

    echo "<pre>";
    echo var_export($result, true);
    echo "</pre>";
}

if (!empty($_GET['test-delete'])) {
    $client->del([$_GET['test-delete']]);
}

if (!empty($_GET['test-list'])) {
    $result = $client->list();

    echo "<pre>";
    echo var_export($result, true);
    echo "</pre>";
}

echo "Vercel Blob Client PHP";
