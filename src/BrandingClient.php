<?php

namespace BBC\BrandingClient;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use function GuzzleHttp\Psr7\parse_header;

class BrandingClient
{
    // Public constants
    const PREVIEW_PARAM = 'branding-theme-version';

    // Private constants
    // @codingStandardsIgnoreStart
    const BRANDING_WEBSERVICE_URL = 'https://branding.files.bbci.co.uk/branding/{env}/projects/{projectId}.json';
    const BRANDING_WEBSERVICE_URL_DEV = 'https://branding.test.files.bbci.co.uk/branding/{env}/projects/{projectId}.json';
    const BRANDING_WEBSERVICE_PREVIEW_URL = 'https://branding.files.bbci.co.uk/branding/{env}/previews/{themeVersionId}.json';
    const BRANDING_WEBSERVICE_PREVIEW_URL_DEV = 'https://branding.test.files.bbci.co.uk/branding/{env}/previews/{themeVersionId}.json';
    // @codingStandardsIgnoreEnd

    const SUPPORTED_ENVIRONMENTS = ['int', 'test', 'live'];

    const FALLBACK_CACHE_DURATION = 1800;

    /** @var Client */
    private $client;

    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * @var array
     *
     * env is the environment to point at. One of 'int', 'test' or 'live'
     * cacheTime is the number of seconds that the result should be stored.
     *   By default this is derived from the HTTP cache headers of the
     *   branding API response so you should not need to set it. Setting this
     *   cacheTime shall override the value from the HTTP cache headers
     */
    private $options = [
        'env' => 'live',
        'cacheKeyPrefix' => 'branding',
        'cacheTime' => null,
    ];

    public function __construct(
        Client $client,
        CacheItemPoolInterface $cache,
        array $options = []
    ) {
        $this->client = $client;
        $this->cache = $cache;

        if (array_key_exists('env', $options) && !in_array($options['env'], self::SUPPORTED_ENVIRONMENTS)) {
            throw new BrandingException(sprintf(
                'Invalid environment supplied, expected one of "%s" but got "%s"',
                implode(', ', self::SUPPORTED_ENVIRONMENTS),
                $options['env']
            ));
        }

        if (array_key_exists('cacheTime', $options) && !(is_int($options['cacheTime']) && $options['cacheTime'] >= 0)) {
            throw new BrandingException(sprintf(
                'Invalid cacheTime supplied, expected a positive integer but got "%s"',
                $options['cacheTime']
            ));
        }

        $this->options = array_merge($this->options, $options);
    }

    public function getContent($projectId, $themeVersionId = null)
    {
        $url = $this->getUrl($projectId, $themeVersionId);
        $cacheKey = $this->options['cacheKeyPrefix'] . '.' . md5($url);

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($cacheKey);
        if (!$cacheItem->isHit()) {
            try {
                $response = $this->client->get($url, [
                    'headers' => ['Accept-Encoding' => 'gzip']
                ]);
                $result = json_decode($response->getBody()->getContents(), true);
            } catch (RequestException $e) {
                throw new BrandingException('Invalid Branding Response. Could not get data from webservice', 0, $e);
            }

            if (!$result || !isset($result['head'])) {
                throw new BrandingException('Invalid Branding Response. Response JSON object was invalid or malformed');
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

        return $this->mapResultToBrandingObject($cacheItem->get());
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

    private function mapResultToBrandingObject(array $branding)
    {
        return new Branding(
            $branding['head'],
            $branding['bodyFirst'],
            $branding['bodyLast'],
            $branding['colours'],
            $branding['options']
        );
    }

    /**
     * Construct the url to request for a given ProjectID and/or ThemeVersionId.
     *
     * @return string
     */
    private function getUrl($projectId, $themeVersionId)
    {
        $env = $this->options['env'];

        // Preview URLs
        if ($themeVersionId) {
            $url = self::BRANDING_WEBSERVICE_PREVIEW_URL;

            if ($env != 'live') {
                $url = self::BRANDING_WEBSERVICE_PREVIEW_URL_DEV;
            }

            return str_replace(['{env}', '{themeVersionId}'], [$env, $themeVersionId], $url);
        }

        $url = self::BRANDING_WEBSERVICE_URL;

        if ($env != 'live') {
            $url = self::BRANDING_WEBSERVICE_URL_DEV;
        }

        return str_replace(['{env}', '{projectId}'], [$env, $projectId], $url);
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
