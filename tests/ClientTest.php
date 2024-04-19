<?php

namespace Tests\VercelBlobPhp;

use DateTime;
use Generator;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use VercelBlobPhp\Client;
use VercelBlobPhp\CommonCreateBlobOptions;
use VercelBlobPhp\CopyBlobResult;
use VercelBlobPhp\Exception\BlobAccessException;
use VercelBlobPhp\Exception\BlobException;
use VercelBlobPhp\Exception\BlobNotFoundException;
use VercelBlobPhp\Exception\BlobServiceNotAvailableException;
use VercelBlobPhp\Exception\BlobServiceRateLimitedException;
use VercelBlobPhp\Exception\BlobStoreNotFoundException;
use VercelBlobPhp\Exception\BlobStoreSuspendedException;
use VercelBlobPhp\Exception\BlobUnknownException;
use VercelBlobPhp\HeadBlobResult;
use VercelBlobPhp\ListBlobResult;
use VercelBlobPhp\ListBlobResultBlob;
use VercelBlobPhp\ListCommandMode;
use VercelBlobPhp\ListCommandOptions;
use VercelBlobPhp\ListFoldedBlobResult;
use VercelBlobPhp\PutBlobResult;

class ClientTest extends TestCase
{
    protected function setUp(): void
    {
        putenv("VERCEL_BLOB_API_URL=blob");
    }

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

    public static function putDataProvider(): Generator
    {
        yield [
            null,
            []
        ];

        yield [
            new CommonCreateBlobOptions(addRandomSuffix: true),
            [
                'x-random-suffix' => true
            ]
        ];

        yield [
            new CommonCreateBlobOptions(contentType: 'application/json'),
            [
                'x-content-type' => 'application/json'
            ]
        ];

        yield [
            new CommonCreateBlobOptions(cacheControlMaxAge: 123),
            [
                'x-cache-control-max-age' => 123
            ]
        ];
    }

    #[DataProvider('putDataProvider')]
    public function testPut(?CommonCreateBlobOptions $options, array $expectedHeaders): void
    {
        $sut = new Client('my-token');

        $sut->setClient(
            $this->mockClient(
                'blob/hello-world.txt',
                'PUT',
                [
                    'body' => 'hello world',
                    'headers' => $expectedHeaders,
                ],
                [
                    'url' => 'url',
                    'downloadUrl' => 'downloadUrl',
                    'pathname' => 'pathname',
                    'contentType' => 'contentType',
                    'contentDisposition' => 'contentDisposition',
                ]
            )
        );

        $this->assertEquals(
            new PutBlobResult(
                'url',
                'downloadUrl',
                'pathname',
                'contentType',
                'contentDisposition'
            ),
            $sut->put('hello-world.txt', 'hello world', $options)
        );
    }

    public static function copyDataProvider(): Generator
    {
        yield [
            null,
            []
        ];

        yield [
            new CommonCreateBlobOptions(addRandomSuffix: true),
            [
                'x-random-suffix' => true
            ]
        ];

        yield [
            new CommonCreateBlobOptions(contentType: 'application/json'),
            [
                'x-content-type' => 'application/json'
            ]
        ];

        yield [
            new CommonCreateBlobOptions(cacheControlMaxAge: 123),
            [
                'x-cache-control-max-age' => 123
            ]
        ];
    }

    #[DataProvider('copyDataProvider')]
    public function testCopy(?CommonCreateBlobOptions $options, array $expectedHeaders): void
    {
        $sut = new Client('my-token');

        $sut->setClient(
            $this->mockClient(
                'blob/hello-world.txt?fromUrl=test-url',
                'PUT',
                [
                    'headers' => $expectedHeaders,
                ],
                [
                    'url' => 'url',
                    'downloadUrl' => 'downloadUrl',
                    'pathname' => 'pathname',
                    'contentType' => 'contentType',
                    'contentDisposition' => 'contentDisposition',
                ]
            )
        );

        $this->assertEquals(
            new CopyBlobResult(
                'url',
                'downloadUrl',
                'pathname',
                'contentType',
                'contentDisposition'
            ),
            $sut->copy('test-url', 'hello-world.txt', $options)
        );
    }

    public function testDel(): void
    {
        $sut = new Client('my-token');

        $sut->setClient(
            $this->mockClient(
                'blob/delete',
                'POST',
                [
                    'json' => [
                        'urls' => [
                            'url1',
                            'url2',
                        ]
                    ]
                ],
                []
            )
        );

        $sut->del(['url1', 'url2']);
    }

    public function testHead(): void
    {
        $sut = new Client('my-token');

        $sut->setClient(
            $this->mockClient(
                'blob?url=test-url',
                'GET',
                [],
                [
                    'url' => 'url',
                    'downloadUrl' => 'downloadUrl',
                    'size' => 1,
                    'uploadedAt' => '2024-01-01 10:00:00',
                    'pathname' => 'pathname',
                    'contentType' => 'contentType',
                    'contentDisposition' => 'contentDisposition',
                    'cacheControl' => 'cacheControl'
                ]
            )
        );

        $this->assertEquals(
            new HeadBlobResult(
                'url',
                'downloadUrl',
                1,
                new DateTime('2024-01-01 10:00:00'),
                'pathname',
                'contentType',
                'contentDisposition',
                'cacheControl'
            ),
            $sut->head('test-url')
        );
    }

    public static function listDataProvider(): Generator
    {
        yield [
            null,
            [
                'blobs' => [
                    [
                        'url' => 'url',
                        'downloadUrl' => 'downloadUrl',
                        'pathname' => 'pathname',
                        'size' => 1,
                        'uploadedAt' => '2024-01-01 10:00:00',
                    ],
                ],
                'cursor' => 'cursor',
                'hasMore' => true,
            ],
            'blob?',
            new ListBlobResult(
                [
                    new ListBlobResultBlob(
                        'url',
                        'downloadUrl',
                        'pathname',
                        1,
                        new DateTime('2024-01-01 10:00:00')
                    )
                ],
                'cursor',
                true
            )
        ];

        yield [
            new ListCommandOptions(mode: ListCommandMode::FOLDED),
            [
                'blobs' => [
                    [
                        'url' => 'url',
                        'downloadUrl' => 'downloadUrl',
                        'pathname' => 'pathname',
                        'size' => 1,
                        'uploadedAt' => '2024-01-01 10:00:00',
                    ],
                ],
                'cursor' => 'cursor',
                'hasMore' => true,
                'folders' => [
                    'folder1',
                    'folder2'
                ]
            ],
            'blob?mode=folded',
            new ListFoldedBlobResult(
                [
                    new ListBlobResultBlob(
                        'url',
                        'downloadUrl',
                        'pathname',
                        1,
                        new DateTime('2024-01-01 10:00:00')
                    )
                ],
                'cursor',
                true,
                [
                    'folder1',
                    'folder2'
                ]
            )
        ];

        yield [
            new ListCommandOptions(cursor: 'cursor'),
            [
                'blobs' => [
                    [
                        'url' => 'url',
                        'downloadUrl' => 'downloadUrl',
                        'pathname' => 'pathname',
                        'size' => 1,
                        'uploadedAt' => '2024-01-01 10:00:00',
                    ],
                ],
                'cursor' => 'cursor',
                'hasMore' => true,
            ],
            'blob?cursor=cursor',
            new ListBlobResult(
                [
                    new ListBlobResultBlob(
                        'url',
                        'downloadUrl',
                        'pathname',
                        1,
                        new DateTime('2024-01-01 10:00:00')
                    )
                ],
                'cursor',
                true
            )
        ];

        yield [
            new ListCommandOptions(limit: 100),
            [
                'blobs' => [
                    [
                        'url' => 'url',
                        'downloadUrl' => 'downloadUrl',
                        'pathname' => 'pathname',
                        'size' => 1,
                        'uploadedAt' => '2024-01-01 10:00:00',
                    ],
                ],
                'cursor' => 'cursor',
                'hasMore' => true,
            ],
            'blob?limit=100',
            new ListBlobResult(
                [
                    new ListBlobResultBlob(
                        'url',
                        'downloadUrl',
                        'pathname',
                        1,
                        new DateTime('2024-01-01 10:00:00')
                    )
                ],
                'cursor',
                true
            )
        ];

        yield [
            new ListCommandOptions(prefix: 'test'),
            [
                'blobs' => [
                    [
                        'url' => 'url',
                        'downloadUrl' => 'downloadUrl',
                        'pathname' => 'pathname',
                        'size' => 1,
                        'uploadedAt' => '2024-01-01 10:00:00',
                    ],
                ],
                'cursor' => 'cursor',
                'hasMore' => true,
            ],
            'blob?prefix=test',
            new ListBlobResult(
                [
                    new ListBlobResultBlob(
                        'url',
                        'downloadUrl',
                        'pathname',
                        1,
                        new DateTime('2024-01-01 10:00:00')
                    )
                ],
                'cursor',
                true
            )
        ];
    }

    #[DataProvider('listDataProvider')]
    public function testList(
        ?ListCommandOptions $options,
        array $response,
        string $expectedUrl,
        ListBlobResult|ListFoldedBlobResult $expectedResult
    ): void {
        $sut = new Client('my-token');

        $sut->setClient(
            $this->mockClient(
                $expectedUrl,
                'GET',
                [],
                $response
            )
        );

        $this->assertEquals(
            $expectedResult,
            $sut->list($options)
        );
    }

    private function mockClient(
        string $url,
        string $method,
        array $options,
        array $response
    ) {
        $clientMock = $this->createMock(\GuzzleHttp\Client::class);

        $responseMock = $this->createMock(ResponseInterface::class);

        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock
            ->method('getContents')
            ->willReturn(json_encode($response));

        $responseMock
            ->method('getBody')
            ->willReturn($bodyMock);

        $clientMock
            ->expects(self::once())
            ->method('request')
            ->with($method, $url, $options)
            ->willReturn($responseMock);

        return $clientMock;
    }
}
