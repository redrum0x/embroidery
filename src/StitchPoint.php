<?php


namespace redrum0x\embroidery;

/**
 * Class StitchPoint
 * @package redrum0x\embroidery
 */
class StitchPoint
{
    /**
     * @var int
     */
    public $x;

    /**
     * @var int
     */
    public $y;

    /**
     * PesPoint constructor.
     * @param int $x
     * @param int $y
     */
    public function __construct(int $x = 0, int $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }
}