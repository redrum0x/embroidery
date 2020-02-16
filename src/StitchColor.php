<?php

namespace redrum0x\embroidery;

/**
 * Class StitchColor
 * @package redrum0x\embroidery
 */
class StitchColor
{
    /**
     * @var int
     */
    public $r;

    /**
     * @var int
     */
    public $g;

    /**
     * @var int
     */
    public $b;

    /**
     * StitchColor constructor.
     * @param int $r
     * @param int $g
     * @param int $b
     */
    public function __construct(int $r = 0, int $g = 0, int $b = 0)
    {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }

}