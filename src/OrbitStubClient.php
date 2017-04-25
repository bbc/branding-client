<?php

namespace BBC\BrandingClient;

use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Psr\Cache\CacheItemInterface;

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
