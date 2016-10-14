<?php

namespace BBC\BrandingClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Doctrine\Common\Cache\Cache;
use DateTime;
use RuntimeException;

class BrandingClient
{
    const BRANDING_WEBSERVICE_URL = 'https://rmp.files.bbci.co.uk/branding/{env}/projects/{projectId}.json';

    const BRANDING_WEBSERVICE_URL_DEV = 'https://rmp.test.files.bbci.co.uk/branding/{env}/projects/{projectId}.json';

    const SUPPORTED_ENVIRONMENTS = ['int', 'test', 'live'];

    const FALLBACK_CACHE_DURATION = 1800;

    /** @var Client */
    private $client;

    /** @var Cache */
    private $cache;

    private $isGuzzle5 = false;

    /**
     * @var array
     *
     * env is the environment to point at. One of 'int', 'test' or 'live'
     * cacheTime is the number of seconds that the branding result should be
     *   stored. By default this is derived from the HTTP cache headers of the
     *   branding API response so you should not need to set it. Setting this
     *   cacheTime shall override the value from the HTTP cache headers
     *
     */
    private $options = [
        'env' => 'live',
        'cacheTime' => null,
    ];

    public function __construct(
        Client $client,
        Cache $cache,
        array $options = []
    ) {
        $this->client = $client;
        $this->cache = $cache;

        $this->isGuzzle5 = !method_exists($this->client, 'sendAsync');

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

        $result = $this->cache->fetch($cacheKey);
        if (!$result) {
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
                $expiryDate = $this->getDateFromHeader($response, 'Expires');
                $currentDate = $this->getDateFromHeader($response, 'Date');

                if ($currentDate && $expiryDate) {
                    $cacheTime = $expiryDate->getTimestamp() - $currentDate->getTimestamp();
                    $cacheTime = ($cacheTime > 0 ? $cacheTime : 0);
                }
            }

            // cache the result
            $this->cache->save($cacheKey, $result, $cacheTime);
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

    private function getDateFromHeader($response, $headerName)
    {
        if ($this->isGuzzle5) {
            //Guzzle 5
            $headerText = $response->getHeader($headerName);
        } else {
            // Guzzle 6
            $headerText = $response->getHeaderLine($headerName);
        }

        if ($headerText) {
            $headerDate = DateTime::createFromFormat('D, d M Y H:i:s O', $headerText);
            if ($headerDate) {
                return $headerDate;
            }
        }

        return null;
    }
}
