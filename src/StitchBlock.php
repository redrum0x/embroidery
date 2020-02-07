<?php

namespace redrum0x\embroidery;

/**
 * Class StitchBlock
 * @package redrum0x\embroidery
 */
class StitchBlock
{
    public $color; // color
    public $colorIndex; // int32
    public $stitchesTotal; // int32
    public $stitches; // array

    /**
     * StitchBlock constructor.
     */
    public function __construct()
    {
        $this->color = new PesColor();
    }
}