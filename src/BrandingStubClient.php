<?php

namespace BBC\BrandingClient;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Psr\Cache\CacheItemInterface;

class BrandingStubClient extends BrandingClient
{
    public function __construct(
        Client $client = null,
        AbstractAdapter $cache = null,
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
