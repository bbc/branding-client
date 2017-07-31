<?php

namespace Tests\BBC\BrandingClient;

use BBC\BrandingClient\Branding;
use BBC\BrandingClient\BrandingClient;
use Symfony\Component\Cache\Adapter\NullAdapter;

class BrandingClientTest extends MultiGuzzleTestCase
{
    public $cache;

    public function setUp()
    {
        $this->cache = new NullAdapter();
    }

    public function testConstructor()
    {
        $expectedDefaultOptions = [
            'env' => 'live',
            'cacheKeyPrefix' => 'branding',
            'cacheTime' => null,
        ];

        $brandingClient = new BrandingClient(
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
            $this->getClient(),
            $this->cache,
            $options
        );


        $this->assertEquals($options, $brandingClient->getOptions());
    }

    /**
     * @expectedException BBC\BrandingClient\BrandingException
     * @expectedExceptionMessage Invalid environment supplied, expected one of "int, test, live" but got "garbage"
     */
    public function testInvalidEnvThrowsException()
    {
        new BrandingClient(
            $this->getClient(),
            $this->cache,
            ['env' => 'garbage']
        );
    }

    /**
     * @expectedException BBC\BrandingClient\BrandingException
     * @expectedExceptionMessage Invalid cacheTime supplied, expected a positive integer but got "-10"
     */
    public function testInvalidCacheTimeThrowsException()
    {
        new BrandingClient(
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

        $brandingClient = new BrandingClient($client, $this->cache, $options);
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

        $brandingClient = new BrandingClient($client, $this->cache);
        $this->assertEquals($expectedContent, $brandingClient->getContent('br-123'));
    }

    /**
     * @expectedException BBC\BrandingClient\BrandingException
     * @expectedExceptionMessage Invalid Branding Response. Could not get data from webservice
     */
    public function testInvalidContentThrowsException()
    {
        $client = $this->getClient([$this->mockInvalidJsonResponse()]);

        $brandingClient = new BrandingClient($client, $this->cache);
        $brandingClient->getContent('br-123');
    }

    /**
     * @expectedException BBC\BrandingClient\BrandingException
     * @expectedExceptionMessage Invalid Branding Response. Response JSON object was invalid or malformed
     */
    public function testMalformedContentThrowsException()
    {
        $client = $this->getClient([$this->mockMalformedJsonResponse()]);

        $brandingClient = new BrandingClient($client, $this->cache);
        $brandingClient->getContent('br-123');
    }

    /**
     * @dataProvider cachingTimesDataProvider
     */
    public function testCachingTimes($options, $headers, $expectedCacheDuration)
    {
        $expectedKey = 'branding.b22b2e21ce267c3879b21fd96939bfd3';

        $client = $this->getClient([$this->mockSuccessfulJsonResponse($headers)]);
        $cache = $this->getMockBuilder('Symfony\Component\Cache\Adapter\NullAdapter')
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(['save'])
            ->getMock();

        $cache->expects($this->once())->method('save')->with($this->callback(
            function ($cacheItemToSave) use ($expectedKey, $expectedCacheDuration) {
                $current = time() + $expectedCacheDuration;
                $this->assertEquals($expectedKey, $cacheItemToSave->getKey());
                $this->assertAttributeEquals($current, 'expiry', $cacheItemToSave);
                return true;
            }
        ));

        $brandingClient = new BrandingClient($client, $cache, $options);
        $brandingClient->getContent('br-123');
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
