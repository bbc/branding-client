<?php

namespace BBC\BrandingClient;

use BBC\ProgrammesCachingLibrary\CacheInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class OrbitStubClient extends OrbitClient
{
    public function __construct(
        LoggerInterface $logger = null,
        Client $client = null,
        CacheInterface $cache = null,
        array $options = []
    ) {
    }

    public function getContent(array $requestParams = [], array $templateParams = [])
    {
        return new Orbit(
            '<orbit-head><orbit-request-params data-values="' .
                htmlspecialchars(json_encode($requestParams)) . '"/><orbit-template-params data-values="' .
                htmlspecialchars(json_encode($templateParams)) . '"/></orbit-head>',
            '<orbit-bodyfirst/>',
            '<orbit-bodylast/>'
        );
    }
}
