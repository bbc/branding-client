<?php

namespace Tests\BBC\BrandingClient;

use PHPUnit_Framework_TestCase;

class MultiGuzzleTestCase extends PHPUnit_Framework_TestCase
{
    protected function getClient(array $mockResponses = [], &$historyContainer = null)
    {
        if ($this->isGuzzle6()) {
            // Mock Requests
            $mockHandler = new \GuzzleHttp\Handler\MockHandler($mockResponses);
            $handler = \GuzzleHttp\HandlerStack::create($mockHandler);

            // History
            if (!is_null($historyContainer)) {
                $handler->push(\GuzzleHttp\Middleware::history($historyContainer));
            }

            return new \GuzzleHttp\Client(['handler' => $handler]);
        }

        $client = new \GuzzleHttp\Client();

        // Mock Requests
        $mock = new \GuzzleHttp\Subscriber\Mock($mockResponses);
        $client->getEmitter()->attach($mock);

        // History
        if (!is_null($historyContainer)) {
            $client->getEmitter()->attach($historyContainer);
        }

        return $client;
    }

    protected function getHistoryContainer()
    {
        if ($this->isGuzzle6()) {
            return [];
        }

        return new \GuzzleHttp\Subscriber\History();
    }

    protected function mockResponse($responseStatus, $responseHeaders, $responseBody)
    {
        if ($this->isGuzzle6()) {
            // Guzzle 6 object
            return new \GuzzleHttp\Psr7\Response(
                $responseStatus,
                $responseHeaders,
                $responseBody
            );
        }

        // Guzzle 5 object
        return new \GuzzleHttp\Message\Response(
            $responseStatus,
            $responseHeaders,
            \GuzzleHttp\Stream\Stream::factory($responseBody)
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

    private function isGuzzle6()
    {
        return method_exists('\GuzzleHttp\Client', 'sendAsync');
    }
}
