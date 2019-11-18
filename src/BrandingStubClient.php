<?php

namespace BBC\BrandingClient;

use BBC\ProgrammesCachingLibrary\CacheInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use Psr\Log\LoggerInterface;

class BrandingStubClient extends BrandingClient
{
    public function __construct(
        LoggerInterface $logger = null,
        Client $client = null,
        CacheInterface $cache = null,
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
