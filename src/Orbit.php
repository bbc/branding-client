<?php

namespace BBC\BrandingClient;

class Orbit
{
    private $head;

    private $bodyFirst;

    private $bodyLast;

    public function __construct(
        $head,
        $bodyFirst,
        $bodyLast
    ) {
        $this->head = $head;
        $this->bodyFirst = $bodyFirst;
        $this->bodyLast = $bodyLast;
    }

    /**
     * Get the ORB head
     * @return string
     */
    public function getHead()
    {
        return $this->head;
    }

    /**
     * Get the ORB BodyFirst
     * @return string
     */
    public function getBodyFirst()
    {
        return $this->bodyFirst;
    }

    /**
     * Get the ORB Bodylast
     * @return string
     */
    public function getBodyLast()
    {
        return $this->bodyLast;
    }
}
