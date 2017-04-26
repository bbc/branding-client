<?php

namespace BBC\BrandingClient;

use GuzzleHttp\Client;
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
        return new Branding(
            '<branding-head/>',
            '<branding-bodyfirst/>',
            '<branding-bodylast/>',
            ['body' => ['bg' => '#eeeeee']],
            []
        );
    }
}
