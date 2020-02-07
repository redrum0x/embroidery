<?php

namespace redrum0x\embroidery;

/**
 * Class PesColor
 * @package redrum0x\embroidery
 */
class PesColor
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
     * PesColor constructor.
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