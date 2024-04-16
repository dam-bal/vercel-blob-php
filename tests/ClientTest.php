<?php

namespace Tests\VercelBlobPhp;

use Generator;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use VercelBlobPhp\Client;
use VercelBlobPhp\Exception\BlobAccessException;
use VercelBlobPhp\Exception\BlobException;
use VercelBlobPhp\Exception\BlobNotFoundException;
use VercelBlobPhp\Exception\BlobServiceNotAvailableException;
use VercelBlobPhp\Exception\BlobServiceRateLimitedException;
use VercelBlobPhp\Exception\BlobStoreNotFoundException;
use VercelBlobPhp\Exception\BlobStoreSuspendedException;
use VercelBlobPhp\Exception\BlobUnknownException;

class ClientTest extends TestCase
{
    public static function requestThrowsCorrectExceptionBasedOnErrorCode(): Generator
    {
        yield [
            'store_suspended',
            BlobStoreSuspendedException::class,
        ];

        yield [
            'forbidden',
            BlobAccessException::class,
        ];

        yield [
            'not_found',
            BlobNotFoundException::class,
        ];

        yield [
            'store_not_found',
            BlobStoreNotFoundException::class,
        ];

        yield [
            'bad_request',
            BlobException::class,
        ];

        yield [
            'service_unavailable',
            BlobServiceNotAvailableException::class,
        ];

        yield [
            'rate_limited',
            BlobServiceRateLimitedException::class,
        ];

        yield [
            'unknown',
            BlobUnknownException::class,
        ];
    }

    #[DataProvider('requestThrowsCorrectExceptionBasedOnErrorCode')]
    public function testRequestThrowsCorrectExceptionBasedOnErrorCode(
        string $error,
        string $expectedException
    ): void {
        $sut = new Client('my-token');

        $clientMock = $this->createMock(\GuzzleHttp\Client::class);

        $sut->setClient($clientMock);

        $response = $this->createMock(ResponseInterface::class);

        if ($expectedException === BlobServiceRateLimitedException::class) {
            $response
                ->method('getHeaderLine')
                ->with('retry-after')
                ->willReturn('60');
        }

        $responseBody = $this->createMock(StreamInterface::class);

        $responseBody
            ->method('getContents')
            ->willReturn(
                json_encode(['error' => ['code' => $error, 'message' => null]])
            );

        $clientMock
            ->method('request')
            ->willThrowException(new ClientException('', $this->createMock(RequestInterface::class), $response));

        $response
            ->method('getBody')
            ->willReturn($responseBody);

        $this->expectException($expectedException);

        $sut->request('/test', 'GET', []);
    }

    public function testRequest(): void
    {
        $sut = new Client('my-token');

        $clientMock = $this->createMock(\GuzzleHttp\Client::class);

        $sut->setClient($clientMock);

        $responseMock = $this->createMock(ResponseInterface::class);

        $clientMock
            ->method('request')
            ->with('GET', '/test', ['json' => ['test']])
            ->willReturn($responseMock);

        $response = $sut->request('/test', 'GET', ['json' => ['test']]);

        $this->assertEquals($responseMock, $response);
    }
}
