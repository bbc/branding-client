<?php

namespace Tests\BBC\BrandingClient;

use BBC\BrandingClient\Branding;
use PHPUnit_Framework_TestCase;

class BrandingTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $expectedColours = ['body' => [ 'bg' => '#000000']];
        $expectedOptions = ['language' => 'en'];

        $branding = new Branding(
            'head',
            'bodyFirst',
            'bodyLast',
            $expectedColours,
            $expectedOptions
        );

        $this->assertEquals('head', $branding->getHead());
        $this->assertEquals('bodyFirst', $branding->getBodyFirst());
        $this->assertEquals('bodyLast', $branding->getBodyLast());
        $this->assertEquals($expectedColours, $branding->getColours());
        $this->assertEquals($expectedOptions, $branding->getOptions());
    }
}
