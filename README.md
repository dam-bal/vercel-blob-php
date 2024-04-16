# Vercel Blob PHP

PHP Client for Vercel Blob Storage.

[Vercel Blob](https://vercel.com/docs/storage/vercel-blob)

## Usage

### Creating Client

```php
$client = new \VercelBlobPhp\Client();
```

### Using Client

#### PUT
```php
$result = $client->put(
    'test.txt',   // path
    'hello world' // content,
    new \VercelBlobPhp\CommonCreateBlobOptions(
        addRandomSuffix: true,      // optional
        contentType: 'text',        // optional
        cacheControlMaxAge: 123,    // optional
    )
);

// $result is instance of PutBlobResult
$result->url
$result->downloadUrl
$result->pathname
$result->contentType
$result->contentDisposition
```

Third argument is optional.

#### DEL
```php
$client->del(['test.txt']);
```

#### COPY

#### HEAD

#### LIST