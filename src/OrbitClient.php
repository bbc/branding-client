<?php

namespace BBC\BrandingClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use function GuzzleHttp\Psr7\parse_header;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use DateTime;
use Mustache_Engine;

class OrbitClient
{
    // Private constants
    const FALLBACK_CACHE_DURATION = 1800;

    const ORBIT_WEBSERVICE_URL = 'https://navigation.{env}api.bbci.co.uk/api';

    const SUPPORTED_ENVIRONMENTS = ['int', 'test', 'live'];

    /** @var Client */
    private $client;

    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * @var array
     *
     * env is the environment to point at. One of 'int', 'test' or 'live'
     * cacheTime is the number of seconds that the result should be stored
     * mustache is the options array that should be passed to Mustache_Engine
     */
    private $options = [
        'env' => 'live',
        'cacheTime' => null,
        'mustache' => [],
    ];

    public function __construct(
        Client $client,
        CacheItemPoolInterface $cache,
        array $options = []
    ) {
        $this->client = $client;
        $this->cache = $cache;

        if (array_key_exists('env', $options) && !in_array($options['env'], self::SUPPORTED_ENVIRONMENTS)) {
            throw new OrbitException(sprintf(
                'Invalid environment supplied, expected one of "%s" but got "%s"',
                implode(', ', self::SUPPORTED_ENVIRONMENTS),
                $options['env']
            ));
        }

        if (array_key_exists('cacheTime', $options) && !(is_int($options['cacheTime']) && $options['cacheTime'] >= 0)) {
            throw new OrbitException(sprintf(
                'Invalid cacheTime supplied, expected a positive integer but got "%s"',
                $options['cacheTime']
            ));
        }

        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param array $requestParams Parameters that are passed to the HTTP call
     *   `language` and `variant` are the main two
     * @param array $templateParams Parameters that are passed to the Orbit template output
     * @throws OrbitException if the call failed or the response is invalid
     * @return Orbit object that contains items to inject into your templates
     *
     * @see https://navigation.api.bbci.co.uk/docs/index.md
     */
    public function getContent(array $requestParams = [], array $templateParams = [])
    {
        $url = $this->getUrl();
        $headers = $this->getRequestHeaders($requestParams);
        $cacheKey = 'BBC_BRANDING_ORBIT_' . md5($url . json_encode($requestParams));

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($cacheKey);
        if (!$cacheItem->isHit()) {
            try {
                $response = $this->client->get($url, [
                    'headers' => $headers
                ]);
                $result = json_decode($response->getBody()->getContents(), true);
            } catch (RequestException $e) {
                throw new OrbitException('Invalid Orbit Response. Could not get data from webservice', 0, $e);
            }

            if (!$result || !isset($result['head'])) {
                throw new OrbitException('Invalid Orbit Response. Response JSON object was invalid or malformed');
            }

            // Determine how long to cache for
            $cacheTime = self::FALLBACK_CACHE_DURATION;
            if ($this->options['cacheTime']) {
                $cacheTime = $this->options['cacheTime'];
            } else {
                $cacheControl = $response->getHeaderLine('cache-control');
                if (isset(parse_header($cacheControl)[0]['max-age'])) {
                    $cacheTime = (int)parse_header($cacheControl)[0]['max-age'];
                } else {
                    $expiryDate = $this->getDateFromHeader($response, 'Expires');
                    $currentDate = $this->getDateFromHeader($response, 'Date');
                    if ($currentDate && $expiryDate) {
                        // Beware of a cache time of 0 as 0 is treated by Doctrine
                        // Cache as "Cache for an infinite time" which is very much
                        // not what we want. -1 will be treated as already expired
                        $cacheTime = $expiryDate->getTimestamp() - $currentDate->getTimestamp();
                        $cacheTime = ($cacheTime > 0 ? $cacheTime : -1);
                    }
                }
            }

            // cache the result
            $cacheItem->set($result);
            $cacheItem->expiresAfter($cacheTime);
            $this->cache->save($cacheItem);
        }

        $cachedResponse = $cacheItem->get();

        $output = $this->renderOrbResponse($cachedResponse, $templateParams);

        return new Orbit(
            $output['head'],
            $output['bodyFirst'],
            $output['bodyLast']
        );
    }

    /**
     * Retrieve the options that have been set
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Construct the hostname, from the environment and URL override if present
     *
     * @throws OrbitException if an invalid environment is set
     * @return string
     */
    private function getUrl()
    {
        $env = '';

        if ($this->options['env'] != 'live') {
            $env = $this->options['env'] . '.';
        }

        return str_replace('{env}', $env, self::ORBIT_WEBSERVICE_URL);
    }

    /**
     * Returns service-specific request headers for the current options
     *
     * @return array
     */
    private function getRequestHeaders(array $options)
    {
        return [
            'Accept' => 'application/ld+json',
            'Accept-Encoding' => 'gzip',
            'Accept-Language' => isset($options['language']) ? $options['language'] : 'en',
            'X-Orb-Variant' => isset($options['variant']) ? $options['variant'] : 'default',
        ];
    }

    /**
     * @param array $result The Orbit result object
     * @param array $params The content parameters to be applied
     * @return array
     */
    protected function renderOrbResponse(array $result, array $params = [])
    {

        $orbitItem = [];
        $orbitFields = ['head', 'bodyFirst', 'bodyLast'];

        if ($params) {
            $mustache = new Mustache_Engine($this->options['mustache']);
            foreach ($orbitFields as $orbitField) {
                $orbitItem[$orbitField] = $mustache->render(
                    $result[$orbitField]['template'],
                    $params
                );
            }
        } else {
            foreach ($orbitFields as $orbitField) {
                $orbitItem[$orbitField] = $result[$orbitField]['html'];
            }
        }

        return $orbitItem;
    }

    private function getDateFromHeader($response, $headerName)
    {
        $headerText = $response->getHeaderLine($headerName);

        if ($headerText) {
            $headerDate = DateTime::createFromFormat('D, d M Y H:i:s O', $headerText);
            if ($headerDate) {
                return $headerDate;
            }
        }

        return null;
    }
}
