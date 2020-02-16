<?php


namespace redrum0x\embroidery;

/**
 * Class StitchPoint
 * @package redrum0x\embroidery
 */
class StitchPoint
{
    public const TYPE_JUMP = 'jump';
    public const TYPE_MOVE = 'move';
    public const TYPE_COLOR_CHANGE = 'color_change';
    /**
     * @var int
     */
    public $x;

    /**
     * @var int
     */
    public $y;

    /**
     * point types (jump, move)
     * @var string
     */
    public $type;

    /**
     * PesPoint constructor.
     * @param int $x
     * @param int $y
     * @param string $type
     */
    public function __construct(int $x = 0, int $y = 0, $type = '')
    {
        $this->x = $x;
        $this->y = $y;
        $this->type = $type;
    }
}