<?php

namespace BBC\BrandingClient;

class Branding
{
    private $head;

    private $bodyFirst;

    private $bodyLast;

    private $colours;

    private $options;

    /**
     * For historic reasons I don't understand, The language parts in options
     * are underscore-delimited (e.g. cy_GB). However RFC 5646 defines that
     * languages be hyphen-delimited (e.g. cy-GB). The RFC 5646 representation
     * is used for the lang attribute in HTML and when defining the Orbit
     * language. To save having to construct the rfc-compliant format multiple
     * times use a cached version.
     *
     * See https://tools.ietf.org/html/rfc5646
     */
    private $rfcLanguage;

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

        // Define some defaults

        // Language
        if (!array_key_exists('language', $this->options)) {
            $this->options['language'] = 'en_GB';
        }

        // Orb Variant
        if (!array_key_exists('orb_variation', $this->options)) {
            $this->options['orb_variation'] = 'default';
        }

        // Orb Header Color
        if (!array_key_exists('orb_header', $this->options)) {
            $this->options['orb_header'] = 'black';
        }

        // Orb Footer Color
        if (!array_key_exists('orb_footer', $this->options)) {
            $this->options['orb_footer'] = 'black';
        }

        // Orb Footer Text Color
        if (!array_key_exists('orb_footer_text', $this->options)) {
            $this->options['orb_footer_text'] = 'light';
        }

        $this->rfcLanguage = str_replace('_', '-', $this->options['language']);
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

    public function getLanguage()
    {
        return $this->rfcLanguage;
    }

    /**
     * @deprecated Use getLanguage instead
     */
    public function getOrbitLanguage()
    {
        return $this->rfcLanguage;
    }

    public function getOrbitVariant()
    {
        return $this->options['orb_variation'];
    }

    public function getOrbitSearchScope()
    {
        $serviceSearchScopes = array(
            'cbbc' => 'cbbc',
            'cbeebies' => 'cbeebies',
            'bbc_radio_cymru' => 'cymru',
            'bbc_radio_cymru_mwy' => 'cymru',
        );
        if (array_key_exists('mastheadServiceId', $this->options) &&
            array_key_exists($this->options['mastheadServiceId'], $serviceSearchScopes)) {
            return $serviceSearchScopes[$this->options['mastheadServiceId']];
        }

        $navBarSearchScopes = array(
            'radio' => 'iplayer:radio',
        );
        if (array_key_exists('showNavBar', $this->options) &&
            array_key_exists($this->options['showNavBar'], $navBarSearchScopes)) {
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
