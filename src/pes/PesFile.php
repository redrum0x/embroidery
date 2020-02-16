<?php

namespace redrum0x\embroidery\pes;

/*
Embroidery Reader - an application to view .pes embroidery designs

Copyright (C) 2009 Nathan Crawford
Converted from C# to php 2009 Robert Heel

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
02111-1307, USA.

A copy of the full GPL 2 license can be found in the docs directory.
You can contact me at http://www.njcrawford.com/contact.php.
*/

use redrum0x\embroidery\ByteReader;
use redrum0x\embroidery\StitchBlock;
use redrum0x\embroidery\StitchColor;
use redrum0x\embroidery\AbstractStitchFile;
use redrum0x\embroidery\StitchPoint;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

class PesFile extends AbstractStitchFile
{
    /**
     * @var StitchPoint
     */
    public $min; // point

    /**
     * PesFile constructor.
     * @param string $filename
     * @throws ReflectionException
     */
    public function __construct(string $filename)
    {
        $this->min = new StitchPoint();
        $this->OpenFile($filename);
    }

    /**
     * @param string $filename
     * @return bool
     * @throws ReflectionException
     */
    public function OpenFile(string $filename): bool
    {
        $file = fopen($filename, 'rb');
        if (!$file) {
            return false;
        }
        // 4 bytes
        $startFileSig = fread($file, 4);
        if ($startFileSig !== '#PES') {
            //this is not a file that we can read
            $this->lastError = 'Missing #PES at beginning of file';
            fclose($file);
            return false;
        }

        // 4 bytes
        fread($file, 4);

        // pecstart
        $pecstart = ByteReader::readInt32($file);

        // Design dimensions
        $this->imageWidth = ByteReader::readInt16($file);
        $this->imageHeight = ByteReader::readInt16($file);

        // Color table
        fseek($file, $pecstart + 48);
        $numColors = ByteReader::readInt8($file) + 1;
        for ($x = 0; $x < $numColors; $x++) {
            $colorList[] = ByteReader::readInt8($file);
        }

        // Stitch data
        fseek($file, $pecstart + 532);
        $thisPartIsDone = false;
        $prevX = 0;
        $prevY = 0;
        $maxX = 0;
        $minX = 0;
        $maxY = 0;
        $minY = 0;
        $colorNum = -1;
        $tempStitches = array();
        while (!$thisPartIsDone && !feof($file)) {
            $val1 = ByteReader::readInt8($file);
            $val2 = ByteReader::readInt8($file);
            if ($val1 === 255 && $val2 === 0) {
                //end of stitches
                $thisPartIsDone = true;

                //add the last block
                $curBlock = new StitchBlock();
                $curBlock->stitches = $tempStitches;
                $curBlock->stitchesTotal = count($tempStitches);
                $colorNum++;
                $colorIndex = $colorList[$colorNum];
                $curBlock->colorIndex = $colorIndex;
                $curBlock->color = $this->getColorFromIndex($colorIndex);
                $this->blocks[] = $curBlock;
            } elseif ($val1 === 254 && $val2 === 176) {
                //color switch, start a new block
                $curBlock = new StitchBlock();
                $curBlock->stitches = $tempStitches;
                $curBlock->stitchesTotal = count($tempStitches);
                $colorNum++;
                $colorIndex = $colorList[$colorNum];
                $curBlock->colorIndex = $colorIndex;
                $curBlock->color = $this->getColorFromIndex($colorIndex);
                $this->blocks[] = $curBlock;

                $tempStitches = array();

                //read useless(?) byte
                fread($file, 1);
            } else {
                $deltaX = 0;
                $deltaY = 0;
                if (($val1 & 128) === 128) {//$80
                    //this is a jump stitch
                    $deltaX = (($val1 & 15) * 256) + $val2;
                    if (($deltaX & 2048) === 2048) {//$0800
                        $deltaX -= 4096;
                    }
                    //read next byte for Y value
                    $val2 = ByteReader::readInt8($file);
                } else {
                    //normal stitch
                    $deltaX = $val1;
                    if ($deltaX > 63) {
                        $deltaX -= 128;
                    }
                }

                if (($val2 & 128) === 128) {//$80
                    //this is a jump stitch
                    $val3 = ByteReader::readInt8($file);
                    $deltaY = (($val2 & 15) * 256) + $val3;
                    if (($deltaY & 2048) === 2048) {
                        $deltaY -= 4096;
                    }
                } else {
                    //normal stitch
                    $deltaY = $val2;
                    if ($deltaY > 63) {
                        $deltaY -= 128;
                    }
                }

                $prevX += $deltaX;
                $prevY += $deltaY;
                $tempStitches[] = new StitchPoint($prevX, $prevY);

                if ($prevX > $maxX) {
                    $maxX = $prevX;
                } elseif ($prevX < $minX) {
                    $minX = $prevX;
                }

                if ($prevY > $maxY) {
                    $maxY = $prevY;
                } elseif ($prevY < $minY) {
                    $minY = $prevY;
                }
            }
        }
        $this->imageWidth = $maxX - $minX;
        $this->imageHeight = $maxY - $minY;
        $this->min->x = $minX;
        $this->min->y = $minY;

        $this->calcColors();
        $this->calcStitches();

        return true;
    }

    /**
     * @param $index
     * @return StitchColor
     * @throws ReflectionException
     */
    private function getColorFromIndex($index): StitchColor
    {
        $colors = [
            1 => [14, 31, 124],
            2 => [10, 85, 163],
            3 => [48, 135, 119],
            4 => [75, 107, 175],
            5 => [237, 23, 31],
            6 => [209, 92, 0],
            7 => [145, 54, 151],
            8 => [228, 154, 203],
            9 => [145, 95, 172],
            10 => [157, 214, 125],
            11 => [232, 169, 0],
            12 => [254, 186, 53],
            13 => [255, 255, 0],
            14 => [112, 188, 31],
            15 => [192, 148, 0],
            16 => [168, 168, 168],
            17 => [123, 111, 0],
            18 => [255, 255, 179],
            19 => [79, 85, 86],
            20 => [0, 0, 0],
            21 => [11, 61, 145],
            22 => [119, 1, 118],
            23 => [41, 49, 51],
            24 => [42, 19, 1],
            25 => [246, 74, 138],
            26 => [178, 118, 36],
            27 => [252, 187, 196],
            28 => [254, 55, 15],
            29 => [240, 240, 240],
            30 => [106, 28, 138],
            31 => [168, 221, 196],
            32 => [37, 132, 187],
            33 => [254, 179, 67],
            34 => [255, 240, 141],
            35 => [208, 166, 96],
            36 => [209, 84, 0],
            37 => [102, 186, 73],
            38 => [19, 74, 70],
            39 => [135, 135, 135],
            40 => [216, 202, 198],
            41 => [67, 86, 7],
            42 => [254, 227, 197],
            43 => [249, 147, 188],
            44 => [0, 56, 34],
            45 => [178, 175, 212],
            46 => [104, 106, 176],
            47 => [239, 227, 185],
            48 => [247, 56, 102],
            49 => [181, 76, 100],
            50 => [19, 43, 26],
            51 => [199, 1, 85],
            52 => [254, 158, 50],
            53 => [168, 222, 235],
            54 => [0, 103, 26],
            55 => [78, 41, 144],
            56 => [47, 126, 32],
            57 => [253, 217, 222],
            58 => [255, 217, 17],
            59 => [9, 91, 166],
            60 => [240, 249, 112],
            61 => [227, 243, 91],
            62 => [255, 200, 100],
            63 => [255, 200, 150],
            64 => [255, 200, 200],
        ];

        if (!isset($colors[$index])) {
            throw new RuntimeException('Color ' . $index . ' invalid');
        }

        $reflector = new ReflectionClass(StitchColor::class);
        return $reflector->newInstanceArgs($colors[$index]);
    }


    /**
     * calc unique colors
     */
    public function calcColors(): void
    {
        foreach ($this->blocks as $block) {
            $this->colors[$block->colorIndex] = $block->color;
        }
    }

    /**
     * calc sitches
     */
    public function calcStitches(): void
    {
        foreach ($this->blocks as $block) {
            $this->countStitches += $block->stitchesTotal;
        }
    }
}


