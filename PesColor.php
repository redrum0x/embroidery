<?php

namespace redrum0x\embroidery;

/**
 * Class PesColor
 * @package redrum0x\embroidery
 */
class PesColor
{
    public $r;
    public $g;
    public $b;

    /**
     * PesColor constructor.
     * @param int $r
     * @param int $g
     * @param int $b
     */
    public function __construct($r = 0, $g = 0, $b = 0)
    {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }

}