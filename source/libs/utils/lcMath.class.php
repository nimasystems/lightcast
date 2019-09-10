<?php

class lcMath
{
    public static function bigRand($length)
    {
        $length = (int)$length;
        $ret = '';

        srand();

        for ($i = 0; $i < $length; $i++) {
            $ret .= rand(0, 9);
        }

        return $ret;
    }

    public static function roundUp($value, $precision)
    {
        $pow = pow(10, $precision);
        return (ceil($pow * $value) + ceil($pow * $value - ceil($pow * $value))) / $pow;
    }
}
