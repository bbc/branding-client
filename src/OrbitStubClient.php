<?php

namespace BBC\BrandingClient;

use GuzzleHttp\Client;
use Doctrine\Common\Cache\CacheProvider;

class OrbitStubClient extends OrbitClient
{
    public function __construct(
        Client $client = null,
        CacheProvider $cache = null,
        array $options = []
    ) {
    }

    public function getContent(array $requestParams = [], array $templateParams = [])
    {
        return new Orbit(
            '<orbit-head/>',
            '<orbit-bodyfirst/>',
            '<orbit-bodylast/>'
        );
    }
}
