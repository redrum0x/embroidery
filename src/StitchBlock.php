<?php

namespace redrum0x\embroidery;

/**
 * Class StitchBlock
 * @package redrum0x\embroidery
 */
class StitchBlock
{
    /**
     * @var PesColor
     */
    public $color;

    /**
     * @var int
     */
    public $colorIndex;

    /**
     * @var int
     */
    public $stitchesTotal;

    /**
     * @var array
     */
    public $stitches;

    /**
     * StitchBlock constructor.
     */
    public function __construct()
    {
        $this->color = new PesColor();
    }
}