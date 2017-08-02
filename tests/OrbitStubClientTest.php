<?php

namespace Tests\BBC\BrandingClient;

use BBC\BrandingClient\Orbit;
use BBC\BrandingClient\OrbitStubClient;
use Symfony\Component\Cache\Adapter\NullAdapter;

class OrbitStubClientTest extends MultiGuzzleTestCase
{
    public function testGetContent()
    {
        $orbitClient = new OrbitStubClient();

        $expectedOrbit = new Orbit(
            '<orbit-head><orbit-request-params data-values="[]"/>' .
                    '<orbit-template-params data-values="[]"/></orbit-head>',
            '<orbit-bodyfirst/>',
            '<orbit-bodylast/>'
        );

        $this->assertEquals($expectedOrbit, $orbitClient->getContent());
    }

    public function testGetContentWithPresentButUnusedConstructor()
    {
        $orbitClient = new OrbitStubClient(
            $this->getClient(),
            new NullAdapter()
        );

        $expectedOrbit = new Orbit(
            '<orbit-head><orbit-request-params data-values="[]"/>' .
                    '<orbit-template-params data-values="[]"/></orbit-head>',
            '<orbit-bodyfirst/>',
            '<orbit-bodylast/>'
        );

        $this->assertEquals($expectedOrbit, $orbitClient->getContent());
    }

    public function testGetContentWithTemplateParameters()
    {
        $orbitClient = new OrbitStubClient();

        $expectedOrbit = new Orbit(
            '<orbit-head><orbit-request-params data-values="[]"/><orbit-template-params data-values=' .
                    '"{&quot;testKey&quot;:&quot;testValue&quot;}"/></orbit-head>',
            '<orbit-bodyfirst/>',
            '<orbit-bodylast/>'
        );

        $this->assertEquals($expectedOrbit, $orbitClient->getContent([], ['testKey' => 'testValue']));
    }

    public function testGetContentWithRequestParameters()
    {
        $orbitClient = new OrbitStubClient();

        $expectedOrbit = new Orbit(
            '<orbit-head><orbit-request-params data-values="{&quot;Accept-Encoding&quot;:&quot;gzip&quot;}"/>' .
                    '<orbit-template-params data-values="[]"/></orbit-head>',
            '<orbit-bodyfirst/>',
            '<orbit-bodylast/>'
        );

        $this->assertEquals(
            $expectedOrbit,
            $orbitClient->getContent(['Accept-Encoding' => 'gzip'])
        );
    }
}
