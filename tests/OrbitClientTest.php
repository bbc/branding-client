<?php

namespace Tests\BBC\BrandingClient;

use BBC\BrandingClient\Orbit;
use BBC\BrandingClient\OrbitClient;
use Doctrine\Common\Cache\ArrayCache;

class OrbitClientTest extends MultiGuzzleTestCase
{
    public $cache;

    public function setUp()
    {
        $this->cache = new ArrayCache();
    }

    public function testConstructor()
    {
        $expectedDefaultOptions = [
            'env' => 'live',
            'cacheTime' => null,
        ];

        $orbitClient = new OrbitClient(
            $this->getClient(),
            $this->cache
        );

        $this->assertEquals($expectedDefaultOptions, $orbitClient->getOptions());
    }

    public function testConstructorCustomOptions()
    {
        $options = [
            'env' => 'test',
            'cacheTime' => 10,
        ];

        $orbitClient = new OrbitClient(
            $this->getClient(),
            $this->cache,
            $options
        );


        $this->assertEquals($options, $orbitClient->getOptions());
    }

    /**
     * @expectedException BBC\BrandingClient\OrbitException
     * @expectedExceptionMessage Invalid environment supplied, expected one of "int, test, live" but got "garbage"
     */
    public function testInvalidEnvThrowsException()
    {
        new OrbitClient(
            $this->getClient(),
            $this->cache,
            ['env' => 'garbage']
        );
    }

    /**
     * @expectedException BBC\BrandingClient\OrbitException
     * @expectedExceptionMessage Invalid cacheTime supplied, expected a positive integer but got "-10"
     */
    public function testInvalidCacheTimeThrowsException()
    {
        new OrbitClient(
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

        $orbitClient = new OrbitClient($client, $this->cache, $options);
        $orbitClient->getContent(...$arguments);

        $this->assertEquals($expectedUrl, $this->getLastRequestUrl($history));
        $this->assertArraySubset($expectedHeaders, $this->getLastRequest($history)->getHeaders());
    }

    public function orbitApiUrlsDataProvider()
    {
        return [
            [['env' => 'live'], [], 'https://navigation.api.bbci.co.uk/api', [
                'Accept' => ['application/ld+json'],
                'Accept-Encoding' => ['gzip'],
            ]],
            [['env' => 'test'], [], 'https://navigation.test.api.bbci.co.uk/api', []],
            [['env' => 'int'], [],  'https://navigation.int.api.bbci.co.uk/api', []],

            // With a language and variant
            [['env' => 'live'], [['language'=> 'cy_CY', 'variant'=> 'cbbc']], 'https://navigation.api.bbci.co.uk/api', [
                'Accept' => ['application/ld+json'],
                'Accept-Encoding' => ['gzip'],
                'X-Orb-Variant' => ['cbbc'],
                'Accept-Language' => ['cy_CY'],
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

        $orbitClient = new OrbitClient($client, $this->cache);
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

        $orbitClient = new OrbitClient($client, $this->cache);
        $this->assertEquals($expectedContent, $orbitClient->getContent([], [
            'skipLinkTarget' => 'skip',
        ]));
    }

    /**
     * @expectedException BBC\BrandingClient\OrbitException
     * @expectedExceptionMessage Invalid Orbit Response. Could not get data from webservice
     */
    public function testInvalidContentThrowsException()
    {
        $client = $this->getClient([$this->mockInvalidJsonResponse()]);

        $orbitClient = new OrbitClient($client, $this->cache);
        $orbitClient->getContent([]);
    }

    /**
     * @expectedException BBC\BrandingClient\OrbitException
     * @expectedExceptionMessage Invalid Orbit Response. Response JSON object was invalid or malformed
     */
    public function testMalformedContentThrowsException()
    {
        $client = $this->getClient([$this->mockMalformedJsonResponse()]);

        $orbitClient = new OrbitClient($client, $this->cache);
        $orbitClient->getContent([]);
    }

    /**
     * @dataProvider cachingTimesDataProvider
     */
    public function testCachingTimes($options, $headers, $expectedCacheDuration)
    {
        $client = $this->getClient([$this->mockSuccessfulJsonResponse($headers)]);

        $cache = $this->createPartialMock('Doctrine\Common\Cache\ArrayCache', ['save']);
        $cache->expects($this->once())->method('save')->with(
            $this->anything(),
            $this->anything(),
            $this->equalTo($expectedCacheDuration)
        );

        $orbitClient = new OrbitClient($client, $cache, $options);
        $orbitClient->getContent([]);
    }

    public function cachingTimesDataProvider()
    {
        return [
            [
                [],
                ['Date' => 'Thu, 13 Oct 2016 16:10:30 GMT', 'Expires' => 'Thu, 13 Oct 2016 16:11:30 GMT'],
                60
            ],
            [
                [],
                ['Date' => 'Thu, 13 Oct 2016 16:10:30 GMT', 'Expires' => 'Thu, 13 Oct 2016 16:18:00 GMT'],
                450
            ],
            // Need both otherwise use default
            [[], ['Expires' => 'Thu, 13 Oct 2016 16:11:30 GMT'], 1800],
            [[], ['Date' => 'Thu, 13 Oct 2016 16:10:30 GMT'], 1800],
            // Prove explicitly setting a cacheTime in the options overrides all
            [
                ['cacheTime' => 234],
                ['Date' => 'Thu, 13 Oct 2016 16:10:30 GMT', 'Expires' => 'Thu, 13 Oct 2016 16:11:30 GMT'],
                234
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
            '@id'=> './',
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
