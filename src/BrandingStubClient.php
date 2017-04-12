<?php

namespace BBC\BrandingClient;

use GuzzleHttp\Client;
use Doctrine\Common\Cache\CacheProvider;

class BrandingStubClient extends BrandingClient
{
    public function __construct(
        Client $client = null,
        CacheProvider $cache = null,
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
