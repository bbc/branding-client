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
            '<branding-head/>',
            '<branding-bodyfirst/>',
            '<branding-bodylast/>',
            ['body' => ['bg' => '#eeeeee']],
            []
        );

        // Just a projectId
        $this->assertEquals($expectedBranding, $brandingClient->getContent('br-123'));
        // With a null themeVersionId
        $this->assertEquals($expectedBranding, $brandingClient->getContent('br-123', null));
        // With an explicit themeVersionId
        $this->assertEquals($expectedBranding, $brandingClient->getContent('br-123', 'themeVersion'));
    }

    public function testGetContentWithPresentButUnusedConstructor()
    {
        $brandingClient = new BrandingStubClient(
            $this->getClient(),
            new ArrayCache()
        );

        $expectedBranding = new Branding(
            '<branding-head/>',
            '<branding-bodyfirst/>',
            '<branding-bodylast/>',
            ['body' => ['bg' => '#eeeeee']],
            []
        );

        $this->assertEquals($expectedBranding, $brandingClient->getContent('br-123'));
    }
}
