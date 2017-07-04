BBC\BrandingClient
==================

A PHP library to load the Branding and Orbit Webservices and cache them for a
short period. These two webservices are used together to provide a content
skeleton for BBC pages hosed outside of the Forge platform.

Eventually Orbit content shall be included within the Branding Webservice's
output, however currently Orbit does not support all the languages Branding
requires to support. Using Orbit with branding is acceptable if you can be
confident that you do not need any World Service languages and you only need to
support English, Welsh, Irish Gaelic and Scots Gaelic.

Installation
-------------

Add this repository to your composer.json, and add bbc/branding-client as a
dependency.

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:bbc/branding-client.git"
        }
    ],
    "require": {
        "bbc/branding-client": "^2.0"
    }
}
```


Usage
-----

Branding shall return branding information, some of which should be then passed
to the call to the Orbit webservice along with blocks of HTML that should be
injected into your page layout.

Create a BrandingClient, passing in an instance of `GuzzleHttp\Client`, an
implementation of `Psr\Cache\CacheItemPoolInterface`, and optionally an options array. Then
call `getContent()` to make a request and return a `Branding` domain model.

After that make a call to the OrbitClient, passing in the Variant and Language
options from the branding information.

The first argument of `BrandingClient->getContent()` is the published BrandingId
you want to render.
The second argument of `BrandingClient->getContent()` is the preview
ThemeVersionId that should be shown. Consumers SHOULD implement this if they
want to support previewing not-yet-published themes.

```php
$httpClient = new \GuzzleHttp\Client();
$cache = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
$brandingOptions = [];
$orbitOptions = [];
$projectId = 'br-0001';
$themeVersionId = null;
// If you want to support the preview query strings suggested by the Branding
// Tool, set the themeVersionId to the following (ideally using your
// framework's prefered mechanism for reading query parameters):
// $themeVersionId = $_GET[\BBC\BrandingClient\BrandingClient::PREVIEW_PARAM];

$brandingClient = new \BBC\BrandingClient\BrandingClient(
    $httpClient,
    $cache,
    $brandingOptions // optional
);

$branding = $brandingClient->getContent($projectId, $themeVersionId);

$orbitClient = new \BBC\BrandingClient\OrbitClient(
    $httpClient,
    $cache
    $orbitOptions // optional
);

$orb = $orbitClient->getContent([
    'variant' => $branding->getOrbitVariant(),
    'language' => $branding->getLanguage(),
], [
    'searchScope' => $branding->getOrbitSearchScope(),
    // Any additional template replacements you need to pass to Orbit
    // See https://navigation.api.bbc.co.uk/docs/index.md for the various
    // template keys available
]);
```

Valid $options keys to pass to the `BrandingClient` are:

* `env`: To set an environment to request Branding from. Must be one of
  'live', 'test' or 'int'. If omitted, shall default to 'live'.
* `cacheTime`: By default the Client uses the cache control headers of the API
  response determine how long to cache for. To override this value set the
  `cacheTime` to a value in seconds.

Valid $options keys to pass to the `OrbitClient` are:

* `env`: To set an environment to request Orbit from. Must be one of
  'live', 'test' or 'int'. If omitted, shall default to 'live'.
* `cacheTime`: By default the Client uses the cache control headers of the API
  response determine how long to cache for. To override this value set the
  `cacheTime` to a value in seconds.
* `mustache`: An array of options to pass to the `Mustache_Engine` such as `cache`.
   Check the [Mustache Wiki for available options](https://github.com/bobthecow/mustache.php/wiki#constructor-options).

### Branding Object

The `Branding` object returned from `BrandingClient->getContent()` is a domain
object that contains information you should pass to the Orbit client and blocks
of content that you should render in your page.

* `$branding->getHead()` should be injected into your `<head>`, directly after
  Orbit's head content.
* `$branding->getBodyFirst()` should be injected at the top of the `<body>`,
  directly after Orbit's bodyFirst content.
* `$branding->getBodyLast()` should be injected at the bottom of the `<body>`,
  directly before Orbit's bodyLast content.
* `$branding->getOrbitThemeClasses()` should be added to your `<html>` element's
  class attribute to give the Orb the correct coloring.

### Orbit Object

The `Orbit` object returned from `OrbitClient->getContent()` is a domain object
that contains information you should pass to the Orbit client and blocks of
content that you should render in your page.

* `$orbit->getHead()` should be injected into your `<head>`
* `$orbit->getBodyFirst()` should be injected at the top of the `<body>`
* `$orbit->getBodyLast()` should be injected at the bottom of the `<body>`


Development
-----------

Install development dependencies with `composer install`.

Run tests and code sniffer with `script/test`.


License
-------

This repository is available under the terms of the Apache 2.0 license.
View the [LICENSE file](LICENSE) for more information.

Copyright (c) 2017 BBC
