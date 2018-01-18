<?php

namespace BBC\BrandingClient;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Cache\CacheItemPoolInterface;

class BrandingStubClient extends BrandingClient
{
    public function __construct(
        Client $client = null,
        CacheItemPoolInterface $cache = null,
        array $options = []
    ) {
    }

    public function getContent($projectId, $themeVersionId = null)
    {
        return $this->getMockBranding();
    }

    public function getContentAsync($projectId, $themeVersionId = null)
    {
        return new FulfilledPromise($this->getMockBranding());
    }

    private function getMockBranding()
    {
        return new Branding(
            '<branding-head/>',
            '<branding-bodyfirst/>',
            '<branding-bodylast/>',
            ['body' => ['bg' => '#eeeeee']],
            []
        );
    }
}
