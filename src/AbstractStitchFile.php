<?php

namespace redrum0x\embroidery;

abstract class AbstractStitchFile
{
    /**
     * @var int
     */
    public $imageWidth;

    /**
     * @var int
     */
    public $imageHeight;

    /**
     * @var StitchBlock[]
     */
    public $blocks;

    /**
     * @var StitchColor[]
     */
    public $colors;

    /**
     * @var int
     */
    public $countStitches;

    abstract public function __construct(string $filename);
}
