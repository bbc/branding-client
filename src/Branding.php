<?php

namespace BBC\BrandingClient;

class Branding
{
    private $head;

    private $bodyFirst;

    private $bodyLast;

    private $colours;

    private $options;

    public function __construct(
        $head,
        $bodyFirst,
        $bodyLast,
        $colours,
        $options
    ) {
        $this->head = $head;
        $this->bodyFirst = $bodyFirst;
        $this->bodyLast = $bodyLast;
        $this->colours = $colours;
        $this->options = $options;
    }

    /**
     * Get the Branding head
     * @return string
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * Get the Branding BodyFirst
     * @return string
     */
    public function getBodyFirst()
    {
        return $this->bodyFirst;
    }

    /**
     * Get the Branding Bodylast
     * @return string
     */
    public function getBodyLast()
    {
        return $this->bodyLast;
    }

    /**
     * Get the Branding Colours
     * @return array
     */
    public function getColours()
    {
        return $this->colours;
    }

    /**
     * Get the Branding Options
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function getOrbitLanguage()
    {
        if (array_key_exists('language', $this->options)) {
            return $this->options['language'];
        }

        return 'en_GB';
    }

    public function getOrbitVariant()
    {
        if (array_key_exists('orb_variation', $this->options)) {
            return $this->options['orb_variation'];
        }

        return 'default';
    }

    public function getOrbitSearchScope()
    {
        $serviceSearchScopes = array(
            'cbbc' => 'cbbc',
            'cbeebies' => 'cbeebies',
            'bbc_radio_cymru' => 'cymru',
            'bbc_radio_cymru_mwy' => 'cymru',
        );
        if (isset($this->options['mastheadServiceId']) && array_key_exists($this->options['mastheadServiceId'], $serviceSearchScopes)) {
            return $serviceSearchScopes[$this->options['mastheadServiceId']];
        }

        $navBarSearchScopes = array(
            'radio' => 'iplayer:radio',
        );
        if (isset($this->options['showNavBar']) && array_key_exists($this->options['showNavBar'], $navBarSearchScopes)) {
            return $navBarSearchScopes[$this->options['showNavBar']];
        }

        return null;
    }

    public function getOrbitThemeClasses()
    {
        $headerLookup = [
            'black' => 'black--white',
            'white' => 'white--black',
            'transparent-dark' => 'semitransparent-dark--white',
            'transparent-medium' => 'semitransparent-medium--white',
            'transparent-light' => 'semitransparent-light--white',
            'transparent' => 'transparent--dark-grey',
            'grey' => 'grey--white',
            'darkgrey' => 'dark-grey--grey',
        ];

        $footerTextLookup = [
            'light' => 'white',
            'dark' => 'dark-grey',
        ];

        $footerLookup = [
            'black' => 'black--white',
            'transparent' => 'transparent--' . $footerTextLookup[$this->options['orb_footer_text']],
            'opaque' => 'semitransparent--white',
            'grey' => 'grey--white',
            'darkgrey' => 'dark-grey--grey',
        ];

        return sprintf(
            'b-header--%s b-footer--%s',
            $headerLookup[$this->options['orb_header']],
            $footerLookup[$this->options['orb_footer']]
        );
    }
}
