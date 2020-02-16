<?php

namespace redrum0x\embroidery;

/**
 * Class ByteReader
 * @package redrum0x\embroidery
 */
class ByteReader
{

    /**
     * @param $file
     * @return int
     */
    public static function readInt8($file): int
    {
        return (ord(fread($file, 1)));
    }

    /**
     * @param $file
     * @return mixed
     */
    public static function readInt16($file)
    {
        $res = unpack('v', fread($file, 2));
        return (array_shift($res));
    }

    /**
     * @param $file
     * @return mixed
     */
    public static function readInt32($file)
    {
        $res = unpack('V', fread($file, 4));
        return (array_shift($res));
    }
    
}