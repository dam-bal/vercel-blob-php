<?php

namespace VercelBlobPhp;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;

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

    public function put(string $path, string $content, ?CommonCreateBlobOptions $options = null): PutBlobResult
    {
        $parsedResponse = $this->parseResponse(
            $this->client->put(
                $this->getApiUrl('/' . $path),
                [
                    'body' => $content,
                    'headers' => $this->getHeadersForCommonCreateBlobOptions($options),
                ]
            )
        );

        return new PutBlobResult(
            $parsedResponse['url'],
            $parsedResponse['downloadUrl'],
            $parsedResponse['pathname'],
            $parsedResponse['contentType'] ?? null,
            $parsedResponse['contentDisposition']
        );
    }

    /**
     * @param string[] $urls
     */
    public function del(array $urls): void
    {
        $this->client->post(
            $this->getApiUrl('/delete'),
            [
                'json' => [
                    'urls' => $urls,
                ]
            ]
        );
    }

    public function copy(string $fromUrl, string $toPathname, ?CommonCreateBlobOptions $options = null): CopyBlobResult
    {
        $parsedResponse = $this->parseResponse(
            $this->client->put(
                $this->getApiUrl(sprintf('/%s?fromUrl=%s', $toPathname, $fromUrl)),
                [
                    'headers' => $this->getHeadersForCommonCreateBlobOptions($options),
                ]
            )
        );

        return new CopyBlobResult(
            $parsedResponse['url'],
            $parsedResponse['downloadUrl'],
            $parsedResponse['pathname'],
            $parsedResponse['contentType'] ?? null,
            $parsedResponse['contentDisposition']
        );
    }

    public function head(string $url): HeadBlobResult
    {
        $parsedResponse = $this->parseResponse($this->client->get($this->getApiUrl(sprintf('?url=%s', $url))));

        return new HeadBlobResult(
            $parsedResponse['url'],
            $parsedResponse['downloadUrl'],
            $parsedResponse['size'],
            new \DateTime($parsedResponse['uploadedAt']),
            $parsedResponse['pathname'],
            $parsedResponse['contentType'],
            $parsedResponse['contentDisposition'],
            $parsedResponse['cacheControl']
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
        $baseUrl = getenv("VERCEL_BLOB_API_URL")
            ? getenv("VERTCEL_BLOB_API_URL")
            : self::BLOB_STORAGE_URL;

        return $baseUrl . $pathname;
    }

    private function getApiToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        return getenv("BLOB_READ_WRITE_TOKEN") ? getenv("BLOB_READ_WRITE_TOKEN") : '';
    }

    private function parseResponse(ResponseInterface $response): array
    {
        return json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
    }
}
