<?php

namespace VercelBlobPhp;

use DateTime;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use VercelBlobPhp\Exception\BlobAccessException;
use VercelBlobPhp\Exception\BlobException;
use VercelBlobPhp\Exception\BlobNotFoundException;
use VercelBlobPhp\Exception\BlobServiceNotAvailableException;
use VercelBlobPhp\Exception\BlobServiceRateLimitedException;
use VercelBlobPhp\Exception\BlobStoreNotFoundException;
use VercelBlobPhp\Exception\BlobStoreSuspendedException;
use VercelBlobPhp\Exception\BlobUnknownException;

class Client
{
    private const BLOB_STORAGE_URL = 'https://blob.vercel-storage.com';
    private const BLOB_API_VERSION = 7;

    private GuzzleClient $client;

    public function __construct(
        private readonly ?string $token = null,
    ) {
        $this->client = new GuzzleClient(
            [
                'headers' => [
                    'x-api-version' => $this->getApiVersion(),
                    'authorization' => sprintf('Bearer %s', $this->getApiToken()),
                ]
            ]
        );
    }

    public function setClient(GuzzleClient $client): void
    {
        $this->client = $client;
    }

    /**
     * @throws BlobAccessException
     * @throws BlobException
     * @throws BlobNotFoundException
     * @throws BlobServiceNotAvailableException
     * @throws BlobServiceRateLimitedException
     * @throws BlobStoreNotFoundException
     * @throws BlobStoreSuspendedException
     * @throws BlobUnknownException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function request(string $uri, string $method, array $options = []): ResponseInterface
    {
        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();

            $body = $this->getResponseBody($response);

            $code = $body['error']['code'] ?? 'unknown_error';
            $message = $body['error']['message'] ?? null;

            throw match ($code) {
                'store_suspended' => new BlobStoreSuspendedException(),
                'forbidden' => new BlobAccessException(),
                'not_found' => new BlobNotFoundException(),
                'store_not_found' => new BlobStoreNotFoundException(),
                'bad_request' => new BlobException($message ?? 'Bad request'),
                'service_unavailable' => new BlobServiceNotAvailableException(),
                'rate_limited' => new BlobServiceRateLimitedException((int)$response->getHeaderLine('retry-after')),
                default => new BlobUnknownException(),
            };
        }

        return $response;
    }

    /**
     * @return PutBlobResult
     * @throws BlobAccessException
     * @throws BlobException
     * @throws BlobNotFoundException
     * @throws BlobServiceNotAvailableException
     * @throws BlobServiceRateLimitedException
     * @throws BlobStoreNotFoundException
     * @throws BlobStoreSuspendedException
     * @throws BlobUnknownException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function put(string $path, string $content, ?CommonCreateBlobOptions $options = null): PutBlobResult
    {
        $body = $this->getResponseBody(
            $this->request(
                $this->getApiUrl('/' . $path),
                'PUT',
                [
                    'body' => $content,
                    'headers' => $this->getHeadersForCommonCreateBlobOptions($options),
                ]
            )
        );

        return new PutBlobResult(
            $body['url'],
            $body['downloadUrl'],
            $body['pathname'],
            $body['contentType'] ?? null,
            $body['contentDisposition']
        );
    }

    /**
     * @param string[] $urls
     * @throws BlobAccessException
     * @throws BlobException
     * @throws BlobNotFoundException
     * @throws BlobServiceNotAvailableException
     * @throws BlobServiceRateLimitedException
     * @throws BlobStoreNotFoundException
     * @throws BlobStoreSuspendedException
     * @throws BlobUnknownException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function del(array $urls): void
    {
        $this->request(
            $this->getApiUrl('/delete'),
            'POST',
            [
                'json' => [
                    'urls' => $urls
                ]
            ]
        );
    }

    /**
     * @return CopyBlobResult
     * @throws BlobAccessException
     * @throws BlobException
     * @throws BlobNotFoundException
     * @throws BlobServiceNotAvailableException
     * @throws BlobServiceRateLimitedException
     * @throws BlobStoreNotFoundException
     * @throws BlobStoreSuspendedException
     * @throws BlobUnknownException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function copy(string $fromUrl, string $toPathname, ?CommonCreateBlobOptions $options = null): CopyBlobResult
    {
        $body = $this->getResponseBody(
            $this->request(
                $this->getApiUrl(sprintf('/%s?fromUrl=%s', $toPathname, $fromUrl)),
                'PUT',
                [
                    'headers' => $this->getHeadersForCommonCreateBlobOptions($options),
                ]
            )
        );

        return new CopyBlobResult(
            $body['url'],
            $body['downloadUrl'],
            $body['pathname'],
            $body['contentType'] ?? null,
            $body['contentDisposition']
        );
    }

    /**
     * @throws BlobAccessException
     * @throws BlobException
     * @throws BlobNotFoundException
     * @throws BlobServiceNotAvailableException
     * @throws BlobServiceRateLimitedException
     * @throws BlobStoreNotFoundException
     * @throws BlobStoreSuspendedException
     * @throws BlobUnknownException
     * @throws GuzzleException
     * @throws JsonException
     * @throws Exception
     */
    public function head(string $url): HeadBlobResult
    {
        $body = $this->getResponseBody(
            $this->request(
                $this->getApiUrl(sprintf('?url=%s', $url)),
                'GET'
            )
        );

        return new HeadBlobResult(
            $body['url'],
            $body['downloadUrl'],
            $body['size'],
            new DateTime($body['uploadedAt']),
            $body['pathname'],
            $body['contentType'],
            $body['contentDisposition'],
            $body['cacheControl']
        );
    }

    /**
     * @throws BlobAccessException
     * @throws BlobException
     * @throws BlobNotFoundException
     * @throws BlobServiceNotAvailableException
     * @throws BlobServiceRateLimitedException
     * @throws BlobStoreNotFoundException
     * @throws BlobStoreSuspendedException
     * @throws BlobUnknownException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function list(?ListCommandOptions $options = null): ListBlobResult|ListFoldedBlobResult
    {
        $queryParams = [];

        if ($options?->cursor) {
            $queryParams['cursor'] = $options->cursor;
        }

        if ($options?->limit) {
            $queryParams['limit'] = $options->limit;
        }

        if ($options?->mode) {
            $queryParams['mode'] = $options->mode->value;
        }

        if ($options?->prefix) {
            $queryParams['prefix'] = $options->prefix;
        }

        $body = $this->getResponseBody(
            $this->request(
                $this->getApiUrl('?' . http_build_query($queryParams, arg_separator: '&')),
                'GET'
            )
        );

        $blobs = array_map(
            static function (array $blob): ListBlobResultBlob {
                return new ListBlobResultBlob(
                    $blob['url'],
                    $blob['downloadUrl'],
                    $blob['pathname'],
                    $blob['size'],
                    new DateTime($blob['uploadedAt'])
                );
            },
            $body['blobs'] ?? []
        );

        if ($options?->mode === ListCommandMode::FOLDED) {
            return new ListFoldedBlobResult(
                $blobs,
                $body['cursor'] ?? null,
                $body['hasMore'],
                $body['folders'] ?? []
            );
        }

        return new ListBlobResult(
            $blobs,
            $body['cursor'] ?? null,
            $body['hasMore'],
        );
    }

    private function getHeadersForCommonCreateBlobOptions(?CommonCreateBlobOptions $options): array
    {
        if (!$options) {
            return [];
        }

        $headers = [];

        if ($options->addRandomSuffix) {
            $headers['x-random-suffix'] = $options->addRandomSuffix;
        }

        if ($options->contentType) {
            $headers['x-content-type'] = $options->contentType;
        }

        if ($options->cacheControlMaxAge) {
            $headers['x-cache-control-max-age'] = $options->cacheControlMaxAge;
        }

        return $headers;
    }

    private function getApiVersion(): int
    {
        if (getenv("VERCEL_BLOB_API_VERSION_OVERRIDE")) {
            return (int)getenv("VERCEL_BLOB_API_VERSION_OVERRIDE");
        }

        return self::BLOB_API_VERSION;
    }

    private function getApiUrl(string $pathname): string
    {
        $baseUrl = getenv("VERCEL_BLOB_API_URL") ?: self::BLOB_STORAGE_URL;

        return $baseUrl . $pathname;
    }

    private function getApiToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        return getenv("BLOB_READ_WRITE_TOKEN") ?: '';
    }

    /**
     * @throws JsonException
     */
    private function getResponseBody(ResponseInterface $response): array
    {
        return json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
    }
}
