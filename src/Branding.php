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
     * The language in options is a Locale identifier as specified by ISO 15897.
     * This means it is underscore-delimited (e.g. cy_GB). This format is used
     * for Symfony translations.
     * Meanwhile RFC 5646 defines that languages be hyphen-delimited
     * (e.g. cy-GB). The RFC 5646 representation is used for the lang attribute
     * in HTML and when defining the Orbit language. To save having to construct
     * the RFC format multiple times store a cached version.
     *
     * See http://www.open-std.org/jtc1/sc22/wg20/docs/n610.pdf
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

    /**
     * Language is hyphen delimited, as per RFC 5646. Use language when defining
     * languages in HTTP/HTML such as when passing a language to Orbit using the
     * Accept-Language header or specifying the lang attribute in HTML.
     */
    public function getLanguage()
    {
        return $this->rfcLanguage;
    }

    /**
     * Local is underscore delimited, as per ISO15897. Use locale when defining
     * Symfony translate languages and with rmp-translate.
     */
    public function getLocale()
    {
        return $this->options['language'];
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

    /**
     * A utility function for building a Navigation item for use when
     * applications that consume branding wish to replace the navigation
     * placeholder with a set of their own nav items.
     * Eventually the template string for this should be provided by the options
     * of a given branding so that this html template is only specified once
     * within in the BrandingTool, rather than having it there and then
     * duplicating it here too.
     */
    public function buildNavItem($text, $href, $linktrack)
    {
        return sprintf(
            '<li class="br-nav__item"><a class="br-nav__link" href="%2$s" data-linktrack="%3$s">%1$s</a></li>',
            $text,
            $href,
            $linktrack
        );
    }

    public function overrideOption(string $option, $value): void
    {
        $this->options[$option] = $value;
    }
}
