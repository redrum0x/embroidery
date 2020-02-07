<?php

namespace redrum0x\embroidery;

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

class PesFile
{
    public $imageWidth;
    public $imageHeight;
    public $pesHeader; // int64
    public $blocks; // stitchBlock
    public $colorTable;
    public $startStitches = 0; // int64
    public $lastError = ''; // string
    public $pesNum = ''; // string
    public $min; // point

    //means we couldn't figure out some or all
    //of the colors, best guess will be used
    private $colorWarning = false; // bool
    private $formatWarning = false; // bool
    private $classWarning = false; // bool

    /**
     * PesFile constructor.
     * @param $filename
     */
    public function __construct($filename)
    {
        $this->OpenFile($filename);
    }

    /**
     * @param $filename
     * @return bool
     */
    public function OpenFile($filename): bool
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
        $pecstart = $this->readInt32($file);

        // Design dimensions
        $this->imageWidth = $this->readInt16($file);
        $this->imageHeight = $this->readInt16($file);

        // Color table
        fseek($file, $pecstart + 48);
        $numColors = $this->readInt8($file) + 1;
        for ($x = 0; $x < $numColors; $x++) {
            $colorList[] = $this->readInt8($file);
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
        $colorIndex = 0;
        $tempStitches = array();
        while (!$thisPartIsDone && !feof($file)) {
            $val1 = $this->readInt8($file);
            $val2 = $this->readInt8($file);
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
                    $val2 = $this->readInt8($file);
                } else {
                    //normal stitch
                    $deltaX = $val1;
                    if ($deltaX > 63) {
                        $deltaX -= 128;
                    }
                }

                if (($val2 & 128) === 128) {//$80
                    //this is a jump stitch
                    $val3 = $this->readInt8($file);
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
                $tempStitches[] = new PesPoint($prevX, $prevY);

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

        return true;
    }

    /**
     * @param $file
     * @return int
     */
    private function readInt8($file): int
    {
        return (ord(fread($file, 1)));
    }

    /**
     * @param $file
     * @return mixed
     */
    private function readInt16($file)
    {
        return (array_shift(unpack('v', fread($file, 2))));
    }

    /**
     * @param $file
     * @return mixed
     */
    private function readInt32($file)
    {
        return (array_shift(unpack('V', fread($file, 4))));
    }

    /**
     * @param $index
     * @return PesColor
     */
    private function getColorFromIndex($index): PesColor
    {
        switch ($index) {
            case 1:
                $color = new PesColor(14, 31, 124);
                break;
            case 2:
                $color = new PesColor(10, 85, 163);
                break;
            case 3:
                $color = new PesColor(48, 135, 119);
                break;
            case 4:
                $color = new PesColor(75, 107, 175);
                break;
            case 5:
                $color = new PesColor(237, 23, 31);
                break;
            case 6:
                $color = new PesColor(209, 92, 0);
                break;
            case 7:
                $color = new PesColor(145, 54, 151);
                break;
            case 8:
                $color = new PesColor(228, 154, 203);
                break;
            case 9:
                $color = new PesColor(145, 95, 172);
                break;
            case 10:
                $color = new PesColor(157, 214, 125);
                break;
            case 11:
                $color = new PesColor(232, 169, 0);
                break;
            case 12:
                $color = new PesColor(254, 186, 53);
                break;
            case 13:
                $color = new PesColor(255, 255, 0);
                break;
            case 14:
                $color = new PesColor(112, 188, 31);
                break;
            case 15:
                $color = new PesColor(192, 148, 0);
                break;
            case 16:
                $color = new PesColor(168, 168, 168);
                break;
            case 17:
                $color = new PesColor(123, 111, 0);
                break;
            case 18:
                $color = new PesColor(255, 255, 179);
                break;
            case 19:
                $color = new PesColor(79, 85, 86);
                break;
            case 20:
                $color = new PesColor(0, 0, 0);
                break;
            case 21:
                $color = new PesColor(11, 61, 145);
                break;
            case 22:
                $color = new PesColor(119, 1, 118);
                break;
            case 23:
                $color = new PesColor(41, 49, 51);
                break;
            case 24:
                $color = new PesColor(42, 19, 1);
                break;
            case 25:
                $color = new PesColor(246, 74, 138);
                break;
            case 26:
                $color = new PesColor(178, 118, 36);
                break;
            case 27:
                $color = new PesColor(252, 187, 196);
                break;
            case 28:
                $color = new PesColor(254, 55, 15);
                break;
            case 29:
                $color = new PesColor(240, 240, 240);
                break;
            case 30:
                $color = new PesColor(106, 28, 138);
                break;
            case 31:
                $color = new PesColor(168, 221, 196);
                break;
            case 32:
                $color = new PesColor(37, 132, 187);
                break;
            case 33:
                $color = new PesColor(254, 179, 67);
                break;
            case 34:
                $color = new PesColor(255, 240, 141);
                break;
            case 35:
                $color = new PesColor(208, 166, 96);
                break;
            case 36:
                $color = new PesColor(209, 84, 0);
                break;
            case 37:
                $color = new PesColor(102, 186, 73);
                break;
            case 38:
                $color = new PesColor(19, 74, 70);
                break;
            case 39:
                $color = new PesColor(135, 135, 135);
                break;
            case 40:
                $color = new PesColor(216, 202, 198);
                break;
            case 41:
                $color = new PesColor(67, 86, 7);
                break;
            case 42:
                $color = new PesColor(254, 227, 197);
                break;
            case 43:
                $color = new PesColor(249, 147, 188);
                break;
            case 44:
                $color = new PesColor(0, 56, 34);
                break;
            case 45:
                $color = new PesColor(178, 175, 212);
                break;
            case 46:
                $color = new PesColor(104, 106, 176);
                break;
            case 47:
                $color = new PesColor(239, 227, 185);
                break;
            case 48:
                $color = new PesColor(247, 56, 102);
                break;
            case 49:
                $color = new PesColor(181, 76, 100);
                break;
            case 50:
                $color = new PesColor(19, 43, 26);
                break;
            case 51:
                $color = new PesColor(199, 1, 85);
                break;
            case 52:
                $color = new PesColor(254, 158, 50);
                break;
            case 53:
                $color = new PesColor(168, 222, 235);
                break;
            case 54:
                $color = new PesColor(0, 103, 26);
                break;
            case 55:
                $color = new PesColor(78, 41, 144);
                break;
            case 56:
                $color = new PesColor(47, 126, 32);
                break;
            case 57:
                $color = new PesColor(253, 217, 222);
                break;
            case 58:
                $color = new PesColor(255, 217, 17);
                break;
            case 59:
                $color = new PesColor(9, 91, 166);
                break;
            case 60:
                $color = new PesColor(240, 249, 112);
                break;
            case 61:
                $color = new PesColor(227, 243, 91);
                break;
            case 62:
                $color = new PesColor(255, 200, 100);
                break;
            case 63:
                $color = new PesColor(255, 200, 150);
                break;
            case 64:
                $color = new PesColor(255, 200, 200);
                break;
            default:
                $color = new PesColor(0, 0, 0);
                $this->colorWarning = true;
                break;
        }
        return $color;
    }
}


