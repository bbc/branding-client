BBC\BrandingClient
==================

A PHP library to load the Branding Webservice and cache it for a short period.
It is used in conjunction with the
[Orbit client](https://github.com/bbc/rmp-orb-client) to provide a content
skeleton for BBC pages hosed outside of the Forge platform.

Eventually Orbit content shall be included within the Branding Webservice's
output, however currently Orbit does not support all the languages Branding
requires to support. Using Orbit with branding is acceptable if you can be
confident that you do not need any World Service languages and you only need to
support English, Welsh Scots and Irish Gaeling and Scots Gaelic.

Installation
-------------

Add this repository to your composer.json, and add bbc/branding-client as a
dependency. Do the same for the rmp-orb-client too:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:bbc/rmp-orb-client.git"
        },
        {
            "type": "vcs",
            "url": "git@github.com:bbc/branding-client.git"
        }
    ],
    "require": {
        "bbc/bbc-orb-client": "^1.0",
        "bbc/branding-client": "^1.0"
    }
}
```


Usage
-----

Branding shall return branding information, some of which should be then passed
to the call to the Orbit webservice along with blocks of HTML that should be
injected into your page layout.

Create a BrandingClient, passing in an instance of `GuzzleHttp\Client`, an
implementation of `Doctrine\Common\Cache`, and optionally an options array. Then
call `getContent()` to make a request and return a `Branding` domain model.

After that make a call to the OrbitClient, passing in the Variant and Language
options from the branding information.

```php
$httpClient = new \GuzzleHttp\Client();
$cache = new \Doctrine\Common\Cache\ArrayCache();
$brandingOptions = [];
$projectId = 'br-0001';

$brandingClient = new \BBC\BrandingClient\BrandingClient(
    $httpClient,
    $cache,
    $brandingOptions // optional
);

$branding = $orbClient->getContent($projectId);

$orbitClient = new \RMP\OrbClient\OrbitClient(
    $httpClient,
    $cache
);

$orb = $orbitClient->getContent([
    'variant' => $branding->getOrbitVariant(),
    'language' => $branding->getOrbitLanguage(),
    'searchScope' => $branding->getOrbitSearchScope(),
    // Any additional config options you need to pass to Orbit
]);
```

Valid $options keys to pass to the `BrandingClient` are:

* `env`: To set an environment to request Branding from. Must be one of
  'live', 'test' or 'int'. If omitted, shall default to 'live'.
* `cacheTime`: To override the default cache time of one day, set this the
  `cacheTime` to a value in seconds. If omitted, shall default to one day.

### Branding Object

The `Branding` object returned from `getContent()` is a domain object that
contains information you should pass to the Orbit client and blocks of content
that you should render in your page.

* `$branding->getHead()` should be injected into your `<head>`, directly after
  Orbit's head content.
* `$branding->getBodyFirst()` should be injected at the top of the `<body>`,
  directly after Orbit's bodyFirst content.
* `$branding->getBodyLast()` should be injected at the bottom of the `<body>`,
  directly before Orbit's bodyLast content.
* `$branding->getOrbitThemeClasses()` should be added to your `<html>` element's
  class attribute to give the Orb the correct coloring.


Development
-----------

Install development dependencies with `composer install`.

Run tests and code sniffer with `script/test`.
