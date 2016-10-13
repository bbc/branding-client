<?php

namespace Tests\BBC\BrandingClient;

use BBC\BrandingClient\Branding;
use BBC\BrandingClient\BrandingStubClient;
use Doctrine\Common\Cache\ArrayCache;

class BrandingStubClientTest extends MultiGuzzleTestCase
{
    public function testGetContent()
    {
        $brandingClient = new BrandingStubClient();

        $expectedBranding = new Branding(
            '<headContent>',
            '<bodyFirstContent>',
            '<bodyLastContent>',
            ['body' => ['bg' => '#eeeeee']],
            []
        );

        $this->assertEquals($expectedBranding, $brandingClient->getContent('br-123'));
    }

    public function testGetContentWithPresentButUnusedConstructor()
    {
        $brandingClient = new BrandingStubClient(
            $this->getClient(),
            new ArrayCache()
        );

        $expectedBranding = new Branding(
            '<headContent>',
            '<bodyFirstContent>',
            '<bodyLastContent>',
            ['body' => ['bg' => '#eeeeee']],
            []
        );

        $this->assertEquals($expectedBranding, $brandingClient->getContent('br-123'));
    }
}
