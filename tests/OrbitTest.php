<?php

namespace Tests\BBC\BrandingClient;

use BBC\BrandingClient\Orbit;
use PHPUnit\Framework\TestCase;

class OrbitTest extends TestCase
{
    public function testConstructor()
    {
        $branding = new Orbit(
            'head',
            'bodyFirst',
            'bodyLast'
        );

        $this->assertEquals('head', $branding->getHead());
        $this->assertEquals('bodyFirst', $branding->getBodyFirst());
        $this->assertEquals('bodyLast', $branding->getBodyLast());
    }
}
