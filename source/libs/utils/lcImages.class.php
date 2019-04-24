<?php

/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com
*/

class lcImages
{
    # this will be used to resize an image with Imagick
    # constraints are MAX width and height
    # imagick will open, resize and close the file
    # returns true on success, false otherwise
    public static function imagickResize($image_filename, $width, $height, $output_filename = null)
    {
        $image_filename = (string)$image_filename;
        $output_filename = isset($output_filename) ? (string)$output_filename : null;
        $width = (float)$width;
        $height = (float)$height;

        if (!$image_filename || !$width || !$height) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        if (!class_exists('Imagick')) {
            throw new lcSystemRequirementException('Imagick is required');
        }

        $thumb = new Imagick();
        $thumb->readImage($image_filename);
        $thumb->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
        $thumb->writeImage(isset($output_filename) ? $output_filename : $image_filename);
        $thumb->clear();
        $thumb->destroy();

        return true;
    }

    public static function img_resize($tmpname, $size, $save_dir, $save_name)
    {
        $save_dir .= (substr($save_dir, -1) != "/") ? "/" : "";

        if (!$gis = getimagesize($tmpname)) {
            throw new lcSystemException('Cannot obtain image size');
        }

        switch ($gis[2]) {
            case "1":
                {
                    $imorig = imagecreatefromgif($tmpname);
                    break;
                }
            case "2":
                {
                    $imorig = imagecreatefromjpeg($tmpname);
                    break;
                }
            case "3":
                {
                    $imorig = imagecreatefrompng($tmpname);
                    break;
                }
            default:
                {
                    $imorig = imagecreatefromjpeg($tmpname);
                }
        }

        if (!$imorig) {
            return false;
        }

        $x = imagesx($imorig);
        $y = imagesy($imorig);

        if ($gis[0] <= $size) {
            $av = $x;
            $ah = $y;
        } else {
            $yc = $y * 1.3333333;
            $d = $x > $yc ? $x : $yc;
            $c = $d > $size ? $size / $d : $size;
            $av = $x * $c;
            $ah = $y * $c;
        }

        $im = imagecreatetruecolor($av, $ah);

        if (!imagecopyresampled($im, $imorig, 0, 0, 0, 0, $av, $ah, $x, $y)) {
            throw new Exception();
        }

        $img = imagejpeg($im, $save_dir . $save_name);

        return $img;
    }

    public static function calcImageSize($input_width, $input_height, $max_width = 0, $max_height = 0)
    {
        $newSize = ['newWidth' => $input_width, 'newHeight' => $input_height];

        if ($max_width > 0) {
            $newSize = self::calcWidth($input_width, $input_height, $max_width);

            if ($max_height > 0 && $newSize['newHeight'] > $max_height) {
                $newSize = self::calcHeight($newSize['newWidth'], $newSize['newHeight'], $max_height);
            }
        }

        if ($max_height > 0) {
            $newSize = self::calcHeight($input_width, $input_height, $max_height);

            if ($max_width > 0 && $newSize['newWidth'] > $max_width) {
                $newSize = self::calcWidth($newSize['newWidth'], $newSize['newHeight'], $max_width);
            }
        }

        return $newSize;
    }

    protected static function calcWidth($input_width, $input_height, $max_width = 0)
    {
        $newWp = (100 * $max_width) / $input_width;
        $newHeight = ($input_height * $newWp) / 100;
        return ['newWidth' => intval($max_width), 'newHeight' => intval($newHeight)];
    }

    protected static function calcHeight($input_width, $input_height, $max_height = 0)
    {
        $newHp = (100 * $max_height) / $input_height;
        $newWidth = ($input_width * $newHp) / 100;
        return ['newWidth' => intval($newWidth), 'newHeight' => intval($max_height)];
    }
}