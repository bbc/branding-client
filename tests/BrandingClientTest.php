<?php

namespace Tests\BBC\BrandingClient;

use BBC\BrandingClient\Branding;
use BBC\BrandingClient\BrandingClient;
use BBC\BrandingClient\BrandingException;
use BBC\ProgrammesCachingLibrary\CacheInterface;
use BBC\ProgrammesCachingLibrary\CacheWithResilience;
use Psr\Log\LoggerInterface;

class BrandingClientTest extends MultiGuzzleTestCase
{
    public $cache;
    public $logger;

    public function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testConstructor()
    {
        $expectedDefaultOptions = [
            'env' => 'live',
            'cacheKeyPrefix' => 'branding',
            'cacheTime' => null,
        ];

        $brandingClient = new BrandingClient(
            $this->logger,
            $this->getClient(),
            $this->cache
        );

        $this->assertEquals($expectedDefaultOptions, $brandingClient->getOptions());
    }

    public function testConstructorCustomOptions()
    {
        $options = [
            'env' => 'test',
            'cacheKeyPrefix' => 'branding.123',
            'cacheTime' => 10,
        ];

        $brandingClient = new BrandingClient(
            $this->logger,
            $this->getClient(),
            $this->cache,
            $options
        );


        $this->assertEquals($options, $brandingClient->getOptions());
    }

    public function testInvalidEnvThrowsException()
    {
        $this->expectException(BrandingException::class);
        $this->expectExceptionMessage('Invalid environment supplied, expected one of "int, test, live" but got "garbage"');
        new BrandingClient(
            $this->logger,
            $this->getClient(),
            $this->cache,
            ['env' => 'garbage']
        );
    }

    public function testInvalidCacheTimeThrowsException()
    {
        $this->expectException(BrandingException::class);
        $this->expectExceptionMessage('Invalid cacheTime supplied, expected a positive integer but got "-10"');
        new BrandingClient(
            $this->logger,
            $this->getClient(),
            $this->cache,
            ['cacheTime' => -10]
        );
    }

    /**
     * @dataProvider brandingApiUrlsDataProvider
     */
    public function testGetContentCallsCorrectUrl($options, $arguments, $expectedUrl)
    {
        $history = $this->getHistoryContainer();

        $client = $this->getClient(
            [$this->mockSuccessfulJsonResponse()],
            $history
        );

        $brandingClient = new BrandingClient($this->logger, $client, $this->cache, $options);
        $brandingClient->getContent(...$arguments);

        $this->assertEquals($expectedUrl, $this->getLastRequestUrl($history));
    }

    public function brandingApiUrlsDataProvider()
    {
        $livePrefix = 'https://branding.files.bbci.co.uk';
        $devPrefix = 'https://branding.test.files.bbci.co.uk';

        return [
            [['env' => 'live'], ['br-123'], $livePrefix . '/branding/live/projects/br-123.json'],
            [['env' => 'test'], ['br-456'], $devPrefix . '/branding/test/projects/br-456.json'],
            [['env' => 'int'], ['br-789'], $devPrefix . '/branding/int/projects/br-789.json'],
            // With an explicitly Null themeVersionId
            [['env' => 'live'], ['br-123', null], $livePrefix . '/branding/live/projects/br-123.json'],
            [['env' => 'test'], ['br-456', null], $devPrefix . '/branding/test/projects/br-456.json'],
            // With a themeVersionId should call the preview URL
            [['env' => 'live'], ['br-123', '456'], $livePrefix . '/branding/live/previews/456.json'],
            [['env' => 'test'], ['br-456', '789'], $devPrefix . '/branding/test/previews/789.json'],
            [['env' => 'int'], ['br-789', '123'], $devPrefix . '/branding/int/previews/123.json'],
        ];
    }

    public function testGetContentReturnsBrandingObject()
    {
        $expectedContent = new Branding(
            'headContent',
            'bodyFirstContent',
            'bodyLastContent',
            ['body' => ['bg' => '#eeeeee']],
            ['language' => 'en']
        );

        $client = $this->getClient([$this->mockSuccessfulJsonResponse()]);

        $brandingClient = new BrandingClient($this->logger, $client, $this->cache);
        $this->assertEquals($expectedContent, $brandingClient->getContent('br-123'));
    }

    public function testGetContentPromiseReturnsBrandingObject()
    {
        $expectedContent = new Branding(
            'headContent',
            'bodyFirstContent',
            'bodyLastContent',
            ['body' => ['bg' => '#eeeeee']],
            ['language' => 'en']
        );

        $client = $this->getClient([$this->mockSuccessfulJsonResponse()]);

        $brandingClient = new BrandingClient($this->logger, $client, $this->cache);
        $promise = $brandingClient->getContentAsync('br-123');
        $this->assertEquals($expectedContent, $promise->wait(true));
    }

    public function testInvalidContentThrowsException()
    {
        $this->expectException(BrandingException::class);
        $this->expectExceptionMessage('Invalid Branding Response. Could not get data from webservice');

        $client = $this->getClient([$this->mockInvalidJsonResponse()]);

        $brandingClient = new BrandingClient($this->logger, $client, $this->cache);
        $brandingClient->getContent('br-123');
    }

    public function testInvalidContentThrowsExceptionWhenPromiseResolved()
    {
        $this->expectException(BrandingException::class);
        $this->expectExceptionMessage('Invalid Branding Response. Could not get data from webservice');

        $client = $this->getClient([$this->mockInvalidJsonResponse()]);

        $brandingClient = new BrandingClient($this->logger, $client, $this->cache);
        $promise = $brandingClient->getContentAsync('br-123');
        $promise->wait(true);
    }

    public function testMalformedContentThrowsException()
    {
        $this->expectException(BrandingException::class);
        $this->expectExceptionMessage('Invalid Branding Response. Response JSON object was invalid or malformed');


        $client = $this->getClient([$this->mockMalformedJsonResponse()]);

        $brandingClient = new BrandingClient($this->logger, $client, $this->cache);
        $brandingClient->getContent('br-123');
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

        $orbitClient = new BrandingClient($this->logger, $client, $cache, $options);
        $orbitClient->getContent('br-123');
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
            'head' => 'headContent',
            'bodyFirst' => 'bodyFirstContent',
            'bodyLast' => 'bodyLastContent',
            'colours' => ['body' => ['bg' => '#eeeeee']],
            'options' => ['language' => 'en'],
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
