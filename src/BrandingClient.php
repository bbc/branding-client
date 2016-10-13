<?php

namespace BBC\BrandingClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Doctrine\Common\Cache\CacheProvider;
use RuntimeException;

class BrandingClient
{
    const BRANDING_WEBSERVICE_URL = 'https://rmp.files.bbci.co.uk/branding/{env}/projects/{projectId}.json';

    const BRANDING_WEBSERVICE_URL_DEV = 'https://rmp.test.files.bbci.co.uk/branding/{env}/projects/{projectId}.json';

    const SUPPORTED_ENVIRONMENTS = ['int', 'test', 'live'];

    /** @var Client */
    private $client;

    /** @var CacheProvider */
    private $cache;

    /**
     * @var array
     *
     * env is the environment to point at. One of 'int', 'test' or 'live'
     * cacheTime is the number of seconds that the branding result should be stored
     */
    private $options = [
        'env' => 'live',
        'cacheTime' => 86400 // One day
    ];

    public function __construct(
        Client $client,
        CacheProvider $cache,
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

    public function getContent($projectId)
    {
        $url = $this->getUrl($projectId);
        $cacheKey = 'BBC_BRANDING_' . md5($url);

        /* Invalidate the cache if new parameters are added */
        $result = $this->cache->fetch($cacheKey);
        if (!$result) {
            try {
                $response = $this->client->get($url);
                $result = json_decode($response->getBody()->getContents(), true);
            } catch (RequestException $e) {
                throw new BrandingException('Invalid Branding Response. Could not get data from webservice', 0, $e);
            }

            if (!$result || !isset($result['head'])) {
                throw new BrandingException('Invalid Branding Response. Response JSON object was invalid or malformed');
            }

            // cache the result
            $this->cache->save($cacheKey, $result, $this->options['cacheTime']);
        }
        return $this->mapResultToBrandingObject($result);
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
     * Construct the url to request for a given project ID.
     *
     * @return string
     */
    private function getUrl($projectId)
    {
        $url = self::BRANDING_WEBSERVICE_URL;
        $env = $this->options['env'];

        if ($this->options['env'] != 'live') {
            $url = self::BRANDING_WEBSERVICE_URL_DEV;
        }

        return str_replace(['{env}', '{projectId}'], [$env, $projectId], $url);
    }
}
