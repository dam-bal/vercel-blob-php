# Vercel Blob PHP

PHP Client for Vercel Blob Storage.

[Vercel Blob](https://vercel.com/docs/storage/vercel-blob)

## Usage

### Creating Client

```php
$client = new \VercelBlobPhp\Client();
```

Client constructor accepts token for blob storage, but if you connected your blob storage to project then you don't need to set it.

### Using Client

#### PUT
```php
$result = $client->put(
    path: 'test.txt',   // path
    content: 'hello world' // content,
    options: new \VercelBlobPhp\CommonCreateBlobOptions(
        addRandomSuffix: true,      // optional
        contentType: 'text',        // optional
        cacheControlMaxAge: 123,    // optional
    )
);
```

Options argument is optional.

#### DEL
```php
$client->del(['test.txt']);
```

#### COPY
```php
$result = $client->copy(
    fromUrl: 'fromUrl',
    toPathname: 'toPathname',
    options: new \VercelBlobPhp\CommonCreateBlobOptions(
        addRandomSuffix: true,      // optional
        contentType: 'text',        // optional
        cacheControlMaxAge: 123,    // optional
    )
);
```

#### HEAD
```php
$result = $client->head('url');
```

#### LIST
```php
$result = $client->list(
    options: new \VercelBlobPhp\ListCommandOptions(
        limit: 100, // optional
        cursor: 'cursor', // optional
        mode: \VercelBlobPhp\ListCommandMode::EXPANDED, // optional
        prefix: 'prefix', // optional
    )
);
```

Options argument is optional.
