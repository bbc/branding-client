<?php

namespace Tests\BBC\BrandingClient;

use PHPUnit_Framework_TestCase;

abstract class MultiGuzzleTestCase extends PHPUnit_Framework_TestCase
{
    protected function getClient(array $mockResponses = [], &$historyContainer = null)
    {
        // Mock Requests
        $mockHandler = new \GuzzleHttp\Handler\MockHandler($mockResponses);
        $handler = \GuzzleHttp\HandlerStack::create($mockHandler);

        // History
        if (!is_null($historyContainer)) {
            $handler->push(\GuzzleHttp\Middleware::history($historyContainer));
        }

        return new \GuzzleHttp\Client(['handler' => $handler]);
    }

    protected function getHistoryContainer()
    {
        return [];
    }

    protected function mockResponse($responseStatus, $responseHeaders, $responseBody)
    {
        // Guzzle 6 object
        return new \GuzzleHttp\Psr7\Response(
            $responseStatus,
            $responseHeaders,
            $responseBody
        );
    }

    protected function getLastRequest($history)
    {
        if (is_array($history)) {
            return $history[0]['request'];
        }

        return $history->getLastRequest();
    }

    protected function getLastRequestUrl($history)
    {
        if (is_array($history)) {
            $lastRequest = $history[0]['request'];
            return (string) $lastRequest->getUri();
        }

        $lastRequest = $history->getLastRequest();
        return $lastRequest->getUrl();
    }
}
