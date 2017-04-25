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
            '<orbit-head/>',
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
            '<orbit-head/>',
            '<orbit-bodyfirst/>',
            '<orbit-bodylast/>'
        );

        $this->assertEquals($expectedOrbit, $orbitClient->getContent());
    }
}
