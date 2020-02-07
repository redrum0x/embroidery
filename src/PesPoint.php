<?php


namespace redrum0x\embroidery;

/**
 * Class PesPoint
 * @package redrum0x\embroidery
 */
class PesPoint
{
    public $x;
    public $y;

    /**
     * PesPoint constructor.
     * @param int $x
     * @param int $y
     */
    public function __construct($x = 0, $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }
}