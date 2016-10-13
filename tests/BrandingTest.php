<?php

namespace Tests\BBC\BrandingClient;

use BBC\BrandingClient\Branding;
use PHPUnit_Framework_TestCase;

class BrandingTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $expectedColours = ['body' => [ 'bg' => '#000000']];
        $expectedOptions = [
            'language' => 'en_GB',
            'orb_variation' => 'default',
            'orb_header' => 'black',
            'orb_footer' => 'black',
            'orb_footer_text' => 'light',
        ];

        $branding = new Branding(
            'head',
            'bodyFirst',
            'bodyLast',
            $expectedColours,
            []
        );

        $this->assertEquals('head', $branding->getHead());
        $this->assertEquals('bodyFirst', $branding->getBodyFirst());
        $this->assertEquals('bodyLast', $branding->getBodyLast());
        $this->assertEquals($expectedColours, $branding->getColours());
        $this->assertEquals($expectedOptions, $branding->getOptions());
        $this->assertEquals('en_GB', $branding->getOrbitLanguage());
        $this->assertEquals('default', $branding->getOrbitVariant());
        $this->assertEquals(null, $branding->getOrbitSearchScope());
        $this->assertEquals(
            'b-header--black--white b-footer--black--white',
            $branding->getOrbitThemeClasses()
        );
    }

    /**
     * @dataProvider seacheScopesDataProvider
     */
    public function testGetOrbitSearchScope($options, $expectedSearchScope)
    {
        $branding = new Branding('', '', '', [], $options);

        $this->assertEquals($expectedSearchScope, $branding->getOrbitSearchScope());
    }

    public function seacheScopesDataProvider()
    {
        return [
            [[], null],
            // For given Service Ids
            [['mastheadServiceId' => 'cbbc'], 'cbbc'],
            [['mastheadServiceId' => 'cbeebies'], 'cbeebies'],
            [['mastheadServiceId' => 'bbc_radio_cymru'], 'cymru'],
            [['mastheadServiceId' => 'bbc_radio_cymru_mwy'], 'cymru'],

            // For Radio
            [['showNavBar' => 'radio'], 'iplayer:radio'],

            // Assert Service setup takes priority
            [['mastheadServiceId' => 'cbbc', 'showNavBar' => 'radio'], 'cbbc'],
        ];
    }
}
