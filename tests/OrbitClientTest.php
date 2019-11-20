<?php

namespace Tests\BBC\BrandingClient;

use BBC\BrandingClient\Orbit;
use BBC\BrandingClient\OrbitClient;
use BBC\BrandingClient\OrbitException;
use BBC\ProgrammesCachingLibrary\CacheWithResilience;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;

class OrbitClientTest extends MultiGuzzleTestCase
{
    public $cache;
    public $logger;

    public function setUp():void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = new CacheWithResilience($this->logger, new NullAdapter(), '', 1, []);
    }

    public function testConstructor()
    {
        $expectedDefaultOptions = [
            'env' => 'live',
            'cacheKeyPrefix' => 'orbit',
            'cacheTime' => null,
            'mustache' => [],
            'useCloudIdcta' => false,
        ];

        $orbitClient = new OrbitClient(
            $this->logger,
            $this->getClient(),
            $this->cache
        );

        $this->assertEquals($expectedDefaultOptions, $orbitClient->getOptions());
    }

    public function testConstructorCustomOptions()
    {
        $options = [
            'env' => 'test',
            'cacheKeyPrefix' => 'orbit.123',
            'cacheTime' => 10,
            'mustache' => ['someconfig'],
            'useCloudIdcta' => true,
        ];

        $orbitClient = new OrbitClient(
            $this->logger,
            $this->getClient(),
            $this->cache,
            $options
        );

        $this->assertEquals($options, $orbitClient->getOptions());
    }

    public function testInvalidEnvThrowsException()
    {
        $this->expectException(OrbitException::class);
        $this->expectExceptionMessage(
            'Invalid environment supplied, expected one of "int, test, stage, live" but got "garbage"'
        );
        new OrbitClient(
            $this->logger,
            $this->getClient(),
            $this->cache,
            ['env' => 'garbage']
        );
    }

    public function testInvalidCacheTimeThrowsException()
    {
        $this->expectException(OrbitException::class);
        $this->expectExceptionMessage('Invalid cacheTime supplied, expected a positive integer but got "-10"');
        new OrbitClient(
            $this->logger,
            $this->getClient(),
            $this->cache,
            ['cacheTime' => -10]
        );
    }

    /**
     * @dataProvider orbitApiUrlsDataProvider
     */
    public function testGetContentCallsCorrectUrl($options, $arguments, $expectedUrl, $expectedHeaders)
    {
        $history = $this->getHistoryContainer();

        $client = $this->getClient(
            [$this->mockSuccessfulJsonResponse()],
            $history
        );

        $orbitClient = new OrbitClient($this->logger, $client, $this->cache, $options);
        $orbitClient->getContent(...$arguments);

        $this->assertEquals($expectedUrl, $this->getLastRequestUrl($history));
        $lastRequestHeaders = $this->getLastRequest($history)->getHeaders();
        foreach ($expectedHeaders as $key => $expectedHeader) {
            self::assertEquals($expectedHeader, $lastRequestHeaders[$key]);
        }
    }

    public function orbitApiUrlsDataProvider()
    {
        return [
            [['env' => 'live'], [], 'https://navigation.api.bbci.co.uk/api', [
                'Accept' => ['application/ld+json'],
                'Accept-Encoding' => ['gzip'],
            ]],
            [['env' => 'test'], [], 'https://navigation.test.api.bbci.co.uk/api', []],
            [['env' => 'int'], [], 'https://navigation.int.api.bbci.co.uk/api', []],

            // With a language and variant
            [['env' => 'live'], [['language' => 'cy_CY', 'variant' => 'cbbc']], 'https://navigation.api.bbci.co.uk/api',
                [
                    'Accept' => ['application/ld+json'],
                    'Accept-Encoding' => ['gzip'],
                    'X-Orb-Variant' => ['cbbc'],
                    'Accept-Language' => ['cy_CY'],
                ]
            ],

            // With cloud idcta option set to true
            [['env' => 'live', 'useCloudIdcta' => true], [], 'https://navigation.api.bbci.co.uk/api', [
                'X-Feature' => ['akamai-idcta'],
            ]],
        ];
    }

    public function testGetContentReturnsOrbitObject()
    {
        $expectedContent = new Orbit(
            'HEAD',
            'BODYFIRST',
            'BODYLAST'
        );

        $client = $this->getClient([$this->mockSuccessfulJsonResponse()]);

        $orbitClient = new OrbitClient($this->logger, $client, $this->cache);
        $this->assertEquals($expectedContent, $orbitClient->getContent());
    }

    public function testGetContentReturnsOrbitObjectWithTemplateParams()
    {
        $expectedContent = new Orbit(
            'HEAD skip',
            'BODYFIRST skip',
            'BODYLAST skip'
        );

        $client = $this->getClient([$this->mockSuccessfulJsonResponse()]);

        $orbitClient = new OrbitClient($this->logger, $client, $this->cache);
        $this->assertEquals($expectedContent, $orbitClient->getContent([], [
            'skipLinkTarget' => 'skip',
        ]));
    }

    public function testInvalidContentThrowsException()
    {
        $this->expectException(OrbitException::class);
        $this->expectExceptionMessage('Invalid Orbit Response. Could not get data from webservice');

        $client = $this->getClient([$this->mockInvalidJsonResponse()]);

        $orbitClient = new OrbitClient($this->logger, $client, $this->cache);
        $orbitClient->getContent([]);
    }

    public function testMalformedContentThrowsException()
    {
        $this->expectException(OrbitException::class);
        $this->expectExceptionMessage('Invalid Orbit Response. Response JSON object was invalid or malformed');
        $client = $this->getClient([$this->mockMalformedJsonResponse()]);

        $orbitClient = new OrbitClient($this->logger, $client, $this->cache);
        $orbitClient->getContent([]);
    }

    /**
     * @dataProvider cachingTimesDataProvider
     */
    public function testCachingTimes($options, $headers, $expectedCacheDuration)
    {
        $client = $this->getClient([$this->mockSuccessfulJsonResponse($headers)]);
        $cache = $this->createMock(CacheWithResilience::class);
        $cache->expects($this->once())->method('setItem')->with(
            $this->anything(),
            $this->anything(),
            $expectedCacheDuration
        );

        $this->expectException(\TypeError::class);
        $orbitClient = new OrbitClient($this->logger, $client, $cache, $options);
        $orbitClient->getContent([]);
    }

    public function cachingTimesDataProvider()
    {
        return [
            'date and expires are set' => [
                [],
                ['Date' => 'Thu, 13 Oct 2016 16:10:30 GMT', 'Expires' => 'Thu, 13 Oct 2016 16:11:30 GMT'],
                60
            ],
            'date and expires are set different values' => [
                [],
                ['Date' => 'Thu, 13 Oct 2016 16:10:30 GMT', 'Expires' => 'Thu, 13 Oct 2016 16:18:00 GMT'],
                450
            ],
            // Need both otherwise use default
            'only expires is set' => [[], ['Expires' => 'Thu, 13 Oct 2016 16:11:30 GMT'], 1800],
            'only date is set' => [[], ['Date' => 'Thu, 13 Oct 2016 16:10:30 GMT'], 1800],
            // Prove explicitly setting a cacheTime in the options overrides all
            'cacheTime takes precedents' => [
                ['cacheTime' => 234],
                [
                    'Date' => 'Thu, 13 Oct 2016 16:10:30 GMT',
                    'Expires' => 'Thu, 13 Oct 2016 16:11:30 GMT',
                    'Cache-Control' => 'max-age=130',
                ],
                234
            ],
            'max-age by itself' => [
                [],
                ['Cache-Control' => 'max-age=120'],
                120
            ],
            'max-age takes precedents over date and expires' => [
                [],
                [
                    'Cache-Control' => 'max-age=130',
                    'Date' => 'Thu, 13 Oct 2016 16:10:30 GMT',
                    'Expires' => 'Thu, 13 Oct 2016 16:11:30 GMT',
                ],
                130
            ],
            'Cache-Control exists without max-age' => [
                [],
                ['Cache-Control' => 'non-legit'],
                1800
            ],
        ];
    }

    private function mockSuccessfulJsonResponse(array $headers = [])
    {
        return $this->mockResponse(200, $headers, json_encode([
            '@context' => [
                '@vocab' => 'http://www.bbc.co.uk/ontologies/webmodules/'
            ],
            '@type' => 'WebModule',
            '@id' => './',
            'head' => [
                'template' => 'HEAD {{skipLinkTarget}}',
                'html' => 'HEAD'
            ],
            'bodyFirst' => [
                'template' => 'BODYFIRST {{skipLinkTarget}}',
                'html' => 'BODYFIRST'
            ],
            'bodyLast' => [
                'template' => 'BODYLAST {{skipLinkTarget}}',
                'html' => 'BODYLAST'
            ],
        ]));
    }

    private function mockInvalidJsonResponse()
    {
        return $this->mockResponse(404, [], '');
    }

    private function mockMalformedJsonResponse()
    {
        return $this->mockResponse(200, [], 'bongos');
    }
}
