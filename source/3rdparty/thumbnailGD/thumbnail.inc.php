<?php
/**
 * thumbnail.inc.php
 *
 * @author        Ian Selby (ian@gen-x-design.com)
 * @copyright    Copyright 2006
 * @version    1.1 (PHP5)
 *
 */

/**
 * PHP class for dynamically resizing, cropping, and rotating images for thumbnail purposes and either displaying them on-the-fly or saving them.
 *
 * TODO: Deprecated. Remove in 1.5
 *
 */
class Thumbnail
{
    /**
     * Error message to display, if any
     *
     * @var string
     */
    protected $errmsg;
    /**
     * Whether or not there is an error
     *
     * @var boolean
     */
    protected $error;
    /**
     * Format of the image file
     *
     * @var string
     */
    protected $format;
    /**
     * File name and path of the image file
     *
     * @var string
     */
    protected $fileName;
    /**
     * Image meta data if any is available (jpeg/tiff) via the exif library
     *
     * @var array
     */
    protected $imageMeta;
    /**
     * Current dimensions of working image
     *
     * @var array
     */
    protected $currentDimensions;
    /**
     * New dimensions of working image
     *
     * @var array
     */
    protected $newDimensions;
    /**
     * Image resource for newly manipulated image
     *
     * @var resource
     */
    protected $newImage;
    /**
     * Image resource for image before previous manipulation
     *
     * @var resource
     */
    protected $oldImage;
    /**
     * Image resource for image being currently manipulated
     *
     * @var resource
     */
    protected $workingImage;
    /**
     * Percentage to resize image by
     *
     * @var int
     */
    protected $percent;
    /**
     * Maximum width of image during resize
     *
     * @var int
     */
    protected $maxWidth;
    /**
     * Maximum height of image during resize
     *
     * @var int
     */
    protected $maxHeight;

    /**
     * Class constructor
     *
     * @param string $fileName
     * @return Thumbnail
     */
    public function __construct($fileName)
    {
        //make sure the GD library is installed
        if (!function_exists("gd_info")) {
            echo 'You do not have the GD Library installed.  This class requires the GD library to function properly.' . "\n";
            echo 'visit http://us2.php.net/manual/en/ref.image.php for more information';
            exit;
        }
        //initialize variables
        $this->errmsg = '';
        $this->error = false;
        $this->currentDimensions = [];
        $this->newDimensions = [];
        $this->fileName = $fileName;
        $this->imageMeta = [];
        $this->percent = 100;
        $this->maxWidth = 0;
        $this->maxHeight = 0;

        //check to see if file exists
        if (!file_exists($this->fileName)) {
            $this->errmsg = 'File not found';
            $this->error = true;
        } //check to see if file is readable
        else if (!is_readable($this->fileName)) {
            $this->errmsg = 'File is not readable';
            $this->error = true;
        }

        //if there are no errors, determine the file format
        if ($this->error == false) {
            //check if gif
            if (stristr(strtolower($this->fileName), '.gif')) $this->format = 'GIF';
            //check if jpg
            else if (stristr(strtolower($this->fileName), '.jpg') || stristr(strtolower($this->fileName), '.jpeg')) $this->format = 'JPG';
            //check if png
            else if (stristr(strtolower($this->fileName), '.png')) $this->format = 'PNG';
            //unknown file format
            else {
                $this->errmsg = 'Unknown file format';
                $this->error = true;
            }
        }

        //initialize resources if no errors
        if ($this->error == false) {
            switch ($this->format) {
                case 'GIF':
                    $this->oldImage = @ImageCreateFromGif($this->fileName);
                    break;
                case 'JPG':
                    $this->oldImage = @ImageCreateFromJpeg($this->fileName);
                    break;
                case 'PNG':
                    $this->oldImage = @ImageCreateFromPng($this->fileName);
                    break;
            }


            $size = GetImageSize($this->fileName);
            $this->currentDimensions = ['width' => $size[0], 'height' => $size[1]];
            $this->newImage = $this->oldImage;
            $this->gatherImageMeta();
        }

        if ($this->error == true) {
            $this->showErrorImage();
        }
    }

    /**
     * Reads selected exif meta data from jpg images and populates $this->imageMeta with appropriate values if found
     *
     */
    private function gatherImageMeta()
    {
        //only attempt to retrieve info if exif exists
        if (function_exists("exif_read_data") && $this->format == 'JPG') {
            $imageData = exif_read_data($this->fileName);
            if (isset($imageData['Make']))
                $this->imageMeta['make'] = ucwords(strtolower($imageData['Make']));
            if (isset($imageData['Model']))
                $this->imageMeta['model'] = $imageData['Model'];
            if (isset($imageData['COMPUTED']['ApertureFNumber'])) {
                $this->imageMeta['aperture'] = $imageData['COMPUTED']['ApertureFNumber'];
                $this->imageMeta['aperture'] = str_replace('/', '', $this->imageMeta['aperture']);
            }
            if (isset($imageData['ExposureTime'])) {
                $exposure = explode('/', $imageData['ExposureTime']);
                $exposure = round($exposure[1] / $exposure[0], -1);
                $this->imageMeta['exposure'] = '1/' . $exposure . ' second';
            }
            if (isset($imageData['Flash'])) {
                if ($imageData['Flash'] > 0) {
                    $this->imageMeta['flash'] = 'Yes';
                } else {
                    $this->imageMeta['flash'] = 'No';
                }
            }
            if (isset($imageData['FocalLength'])) {
                $focus = explode('/', $imageData['FocalLength']);
                $this->imageMeta['focalLength'] = round($focus[0] / $focus[1], 2) . ' mm';
            }
            if (isset($imageData['DateTime'])) {
                $date = $imageData['DateTime'];
                $date = explode(' ', $date);
                $date = str_replace(':', '-', $date[0]) . ' ' . $date[1];
                $this->imageMeta['dateTaken'] = date('m/d/Y g:i A', strtotime($date));
            }
        }
    }

    /**
     * Displays error image
     *
     */
    private function showErrorImage()
    {
        header('Content-type: image/png');
        $errImg = ImageCreate(220, 25);
        $bgColor = imagecolorallocate($errImg, 0, 0, 0);
        $fgColor1 = imagecolorallocate($errImg, 255, 255, 255);
        $fgColor2 = imagecolorallocate($errImg, 255, 0, 0);
        imagestring($errImg, 3, 6, 6, 'Error:', $fgColor2);
        imagestring($errImg, 3, 55, 6, $this->errmsg, $fgColor1);
        imagepng($errImg);
        imagedestroy($errImg);
    }

    /**
     * Class destructor
     *
     */
    public function __destruct()
    {
        if (is_resource($this->newImage)) @ImageDestroy($this->newImage);
        if (is_resource($this->oldImage)) @ImageDestroy($this->oldImage);
        if (is_resource($this->workingImage)) @ImageDestroy($this->workingImage);
    }

    /**
     * Resizes image to maxWidth x maxHeight
     *
     * @param int $maxWidth
     * @param int $maxHeight
     */
    public function resize($maxWidth = 0, $maxHeight = 0, $noproportinal = false)
    {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;

        // do not allow overflowing the image itself
        $this->maxWidth = ($this->maxWidth > $this->currentDimensions['width']) ? $this->currentDimensions['width'] : $this->maxWidth;
        $this->maxHeight = ($this->maxHeight > $this->currentDimensions['height']) ? $this->currentDimensions['height'] : $this->maxHeight;

        if (!$noproportinal) {
            $this->calcImageSize($this->currentDimensions['width'], $this->currentDimensions['height']);
        } else {
            $this->newDimensions['newWidth'] = $maxWidth;
            $this->newDimensions['newHeight'] = $maxHeight;
        }

        if (function_exists("ImageCreateTrueColor")) {
            $this->workingImage = ImageCreateTrueColor($this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
        } else {
            $this->workingImage = ImageCreate($this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
        }

        if (!
        ImageCopyResampled(
            $this->workingImage,
            $this->oldImage,
            0,
            0,
            0,
            0,
            $this->newDimensions['newWidth'],
            $this->newDimensions['newHeight'],
            $this->currentDimensions['width'],
            $this->currentDimensions['height']
        )
        )
            return false;

        $this->oldImage = $this->workingImage;
        $this->newImage = $this->workingImage;
        $this->currentDimensions['width'] = $this->newDimensions['newWidth'];
        $this->currentDimensions['height'] = $this->newDimensions['newHeight'];

        return true;
    }

    /**
     * Calculates new image size based on width and height, while constraining to maxWidth and maxHeight
     *
     * @param int $width
     * @param int $height
     */
    protected function calcImageSize($width, $height)
    {
        $newSize = ['newWidth' => $width, 'newHeight' => $height];

        if ($this->maxWidth > 0) {

            $newSize = $this->calcWidth($width, $height);

            if ($this->maxHeight > 0 && $newSize['newHeight'] > $this->maxHeight) {
                $newSize = $this->calcHeight($newSize['newWidth'], $newSize['newHeight']);
            }

            //$this->newDimensions = $newSize;
        }

        if ($this->maxHeight > 0) {
            $newSize = $this->calcHeight($width, $height);

            if ($this->maxWidth > 0 && $newSize['newWidth'] > $this->maxWidth) {
                $newSize = $this->calcWidth($newSize['newWidth'], $newSize['newHeight']);
            }

            //$this->newDimensions = $newSize;
        }

        $this->newDimensions = $newSize;

        return $newSize;
    }

    /**
     * Calculates new image width
     *
     * @param int $width
     * @param int $height
     * @return array
     */
    protected function calcWidth($width, $height)
    {
        $newWp = (100 * $this->maxWidth) / $width;
        $newHeight = ($height * $newWp) / 100;
        return ['newWidth' => intval($this->maxWidth), 'newHeight' => intval($newHeight)];
    }

    /**
     * Calculates new image height
     *
     * @param int $width
     * @param int $height
     * @return array
     */
    protected function calcHeight($width, $height)
    {
        $newHp = (100 * $this->maxHeight) / $height;
        $newWidth = ($width * $newHp) / 100;
        return ['newWidth' => intval($newWidth), 'newHeight' => intval($this->maxHeight)];
    }

    /**
     * Resizes the image by $percent percent
     *
     * @param int $percent
     */
    public function resizePercent($percent = 0)
    {
        $this->percent = $percent;

        $this->calcImageSizePercent($this->currentDimensions['width'], $this->currentDimensions['height']);

        if (function_exists("ImageCreateTrueColor")) {
            $this->workingImage = ImageCreateTrueColor($this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
        } else {
            $this->workingImage = ImageCreate($this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
        }

        if (!
        ImageCopyResampled(
            $this->workingImage,
            $this->oldImage,
            0,
            0,
            0,
            0,
            $this->newDimensions['newWidth'],
            $this->newDimensions['newHeight'],
            $this->currentDimensions['width'],
            $this->currentDimensions['height']
        )
        )
            return false;

        $this->oldImage = $this->workingImage;
        $this->newImage = $this->workingImage;
        $this->currentDimensions['width'] = $this->newDimensions['newWidth'];
        $this->currentDimensions['height'] = $this->newDimensions['newHeight'];

        return true;
    }

    /**
     * Calculates new image size based percentage
     *
     * @param int $width
     * @param int $height
     */
    private function calcImageSizePercent($width, $height)
    {
        if ($this->percent > 0) {
            $this->newDimensions = $this->calcPercent($width, $height);
        }
    }

    /**
     * Calculates new image size based on percentage
     *
     * @param int $width
     * @param int $height
     * @return array
     */
    protected function calcPercent($width, $height)
    {
        $newWidth = ($width * $this->percent) / 100;
        $newHeight = ($height * $this->percent) / 100;
        return ['newWidth' => intval($newWidth), 'newHeight' => intval($newHeight)];
    }

    /**
     * Crops the image from calculated center in a square of $cropSize pixels
     *
     * @param int $cropSize
     */
    public function cropFromCenter($cropSize)
    {
        if ($cropSize > $this->currentDimensions['width']) $cropSize = $this->currentDimensions['width'];
        if ($cropSize > $this->currentDimensions['height']) $cropSize = $this->currentDimensions['height'];

        $cropX = intval(($this->currentDimensions['width'] - $cropSize) / 2);
        $cropY = intval(($this->currentDimensions['height'] - $cropSize) / 2);

        if (function_exists("ImageCreateTrueColor")) {
            $this->workingImage = ImageCreateTrueColor($cropSize, $cropSize);
        } else {
            $this->workingImage = ImageCreate($cropSize, $cropSize);
        }

        imagecopyresampled(
            $this->workingImage,
            $this->oldImage,
            0,
            0,
            $cropX,
            $cropY,
            $cropSize,
            $cropSize,
            $cropSize,
            $cropSize
        );

        $this->oldImage = $this->workingImage;
        $this->newImage = $this->workingImage;
        $this->currentDimensions['width'] = $cropSize;
        $this->currentDimensions['height'] = $cropSize;
    }

    /**
     * Advanced cropping function that crops an image using $startX and $startY as the upper-left hand corner.
     *
     * @param int $startX
     * @param int $startY
     * @param int $width
     * @param int $height
     */
    public function crop($startX, $startY, $width, $height)
    {
        //make sure the cropped area is not greater than the size of the image
        if ($width > $this->currentDimensions['width']) $width = $this->currentDimensions['width'];
        if ($height > $this->currentDimensions['height']) $height = $this->currentDimensions['height'];
        //make sure not starting outside the image
        if (($startX + $width) > $this->currentDimensions['width']) $startX = ($this->currentDimensions['width'] - $width);
        if (($startY + $height) > $this->currentDimensions['height']) $startY = ($this->currentDimensions['height'] - $height);
        if ($startX < 0) $startX = 0;
        if ($startY < 0) $startY = 0;

        if (function_exists("ImageCreateTrueColor")) {
            $this->workingImage = ImageCreateTrueColor($width, $height);
        } else {
            $this->workingImage = ImageCreate($width, $height);
        }

        imagecopyresampled(
            $this->workingImage,
            $this->oldImage,
            0,
            0,
            $startX,
            $startY,
            $width,
            $height,
            $width,
            $height
        );

        $this->oldImage = $this->workingImage;
        $this->newImage = $this->workingImage;
        $this->currentDimensions['width'] = $width;
        $this->currentDimensions['height'] = $height;
    }

    /**
     * Saves image as $name (can include file path), with quality of # percent if file is a jpeg
     *
     * @param string $name
     * @param int $quality
     */
    public function save($name, $quality = 100)
    {
        $this->show($quality, $name);
    }

    /**
     * Outputs the image to the screen, or saves to $name if supplied.  Quality of JPEG images can be controlled with the $quality variable
     *
     * @param int $quality
     * @param string $name
     */
    public function show($quality = 100, $name = '')
    {
        switch ($this->format) {
            case 'GIF':
                if ($name != '') {
                    ImageGif($this->newImage, $name);
                } else {
                    header('Content-type: image/gif');
                    ImageGif($this->newImage);
                }
                break;
            case 'JPG':
                if ($name != '') {
                    ImageJpeg($this->newImage, $name, $quality);
                } else {
                    header('Content-type: image/jpeg');
                    ImageJpeg($this->newImage, '', $quality);
                }
                break;
            case 'PNG':
                if ($name != '') {
                    ImagePng($this->newImage, $name);
                } else {
                    header('Content-type: image/png');
                    ImagePng($this->newImage);
                }
                break;
        }
    }

    /**
     * Creates Apple-style reflection under image, optionally adding a border to main image
     *
     * @param int $percent
     * @param int $reflection
     * @param int $white
     * @param bool $border
     * @param string $borderColor
     */
    public function createReflection($percent, $reflection, $white, $border = true, $borderColor = '#a4a4a4')
    {
        $width = $this->currentDimensions['width'];
        $height = $this->currentDimensions['height'];

        $reflectionHeight = intval($height * ($reflection / 100));
        $newHeight = $height + $reflectionHeight;
        $reflectedPart = $height * ($percent / 100);

        $this->workingImage = ImageCreateTrueColor($width, $newHeight);

        ImageAlphaBlending($this->workingImage, true);

        $colorToPaint = ImageColorAllocateAlpha($this->workingImage, 255, 255, 255, 0);
        ImageFilledRectangle($this->workingImage, 0, 0, $width, $newHeight, $colorToPaint);

        imagecopyresampled(
            $this->workingImage,
            $this->newImage,
            0,
            0,
            0,
            $reflectedPart,
            $width,
            $reflectionHeight,
            $width,
            ($height - $reflectedPart));
        $this->imageFlipVertical();

        imagecopy($this->workingImage, $this->newImage, 0, 0, 0, 0, $width, $height);

        imagealphablending($this->workingImage, true);

        for ($i = 0; $i < $reflectionHeight; $i++) {
            $colorToPaint = imagecolorallocatealpha($this->workingImage, 255, 255, 255, ($i / $reflectionHeight * -1 + 1) * $white);
            imagefilledrectangle($this->workingImage, 0, $height + $i, $width, $height + $i, $colorToPaint);
        }

        if ($border == true) {
            $rgb = $this->hex2rgb($borderColor, false);
            $colorToPaint = imagecolorallocate($this->workingImage, $rgb[0], $rgb[1], $rgb[2]);
            imageline($this->workingImage, 0, 0, $width, 0, $colorToPaint); //top line
            imageline($this->workingImage, 0, $height, $width, $height, $colorToPaint); //bottom line
            imageline($this->workingImage, 0, 0, 0, $height, $colorToPaint); //left line
            imageline($this->workingImage, $width - 1, 0, $width - 1, $height, $colorToPaint); //right line
        }

        $this->oldImage = $this->workingImage;
        $this->newImage = $this->workingImage;
        $this->currentDimensions['width'] = $width;
        $this->currentDimensions['height'] = $newHeight;
    }

    /**
     * Inverts working image, used by reflection function
     *
     */
    private function imageFlipVertical()
    {
        $x_i = imagesx($this->workingImage);
        $y_i = imagesy($this->workingImage);

        for ($x = 0; $x < $x_i; $x++) {
            for ($y = 0; $y < $y_i; $y++) {
                imagecopy($this->workingImage, $this->workingImage, $x, $y_i - $y - 1, $x, $y, 1, 1);
            }
        }
    }

    /**
     * Converts hexidecimal color value to rgb values and returns as array/string
     *
     * @param string $hex
     * @param bool $asString
     * @return array|string
     */
    private function hex2rgb($hex, $asString = false)
    {
        // strip off any leading #
        if (0 === strpos($hex, '#')) {
            $hex = substr($hex, 1);
        } else if (0 === strpos($hex, '&H')) {
            $hex = substr($hex, 2);
        }

        // break into hex 3-tuple
        $cutpoint = ceil(strlen($hex) / 2) - 1;
        $rgb = explode(':', wordwrap($hex, $cutpoint, ':', $cutpoint), 3);

        // convert each tuple to decimal
        $rgb[0] = (isset($rgb[0]) ? hexdec($rgb[0]) : 0);
        $rgb[1] = (isset($rgb[1]) ? hexdec($rgb[1]) : 0);
        $rgb[2] = (isset($rgb[2]) ? hexdec($rgb[2]) : 0);

        return ($asString ? "{$rgb[0]} {$rgb[1]} {$rgb[2]}" : $rgb);
    }

    /**
     * Rotates image either 90 degrees clockwise or counter-clockwise
     *
     * @param string $direction
     */
    public function rotateImage($direction = 'CW')
    {
        if ($direction == 'CW') {
            $this->workingImage = imagerotate($this->workingImage, -90, 0);
        } else {
            $this->workingImage = imagerotate($this->workingImage, 90, 0);
        }
        $newWidth = $this->currentDimensions['height'];
        $newHeight = $this->currentDimensions['width'];
        $this->oldImage = $this->workingImage;
        $this->newImage = $this->workingImage;
        $this->currentDimensions['width'] = $newWidth;
        $this->currentDimensions['height'] = $newHeight;
    }

    /**
     * Returns the current width of the image
     *
     * @return int
     */
    protected function getCurrentWidth()
    {
        return $this->currentDimensions['width'];
    }

    /**
     * Returns the current height of the image
     *
     * @return int
     */
    protected function getCurrentHeight()
    {
        return $this->currentDimensions['height'];
    }
}

class LightcastThumbnail extends Thumbnail
{
    const MAX_HEIGHT = 2300;
    const MAX_WIDTH = 2300;

    public function cropResizeCentered($width, $height)
    {
        # check dimensions
        if (
            $this->currentDimensions['height'] > self::MAX_HEIGHT ||
            $this->currentDimensions['height'] < 1 ||
            $this->currentDimensions['width'] > self::MAX_WIDTH ||
            $this->currentDimensions['width'] < 1
        ) {
            return false;
        }

        # if image is smaller, create an empty canvas and center it onto it
        if ($this->currentDimensions['height'] < $height && $this->currentDimensions['width'] < $width) {
            if (function_exists("ImageCreateTrueColor")) {
                $this->workingImage = ImageCreateTrueColor($width, $height);
            } else {
                $this->workingImage = ImageCreate($width, $height);
            }

            $white = imagecolorallocate($this->workingImage, 255, 255, 255);
            imagefill($this->workingImage, 0, 0, $white);

            imagecopy($this->workingImage, $this->newImage,
                ($width - $this->currentDimensions['width']) / 2,
                ($height - $this->currentDimensions['height']) / 2,
                0, 0, $this->currentDimensions['width'], $this->currentDimensions['height']);

            $this->newImage = $this->workingImage;
        } else
            # if the proportion of original/new is equal - just resize and return
            if ($width / $height == $this->currentDimensions['width'] / $this->currentDimensions['height']) {
                $this->resize($width, $height);
            } else
                # if the original HEIGHT is more than the original WIDTH, resize by WIDTH and crop the leftover HEIGHT from the wanted HEIGHT
                if ($this->currentDimensions['height'] > $this->currentDimensions['width']) {
                    $this->resize($width, 0);

                    $fromX = 0;
                    $fromY = ($this->currentDimensions['height'] - $height) / 2;
                    $this->crop($fromX, $fromY, $width, $height);
                } else
                    # if the original WIDTH is more than the original HEIGHT, resize by HEIGHT and crop the leftover WIDTH from the wanted WIDTH
                    if ($this->currentDimensions['height'] <= $this->currentDimensions['width']) {
                        $this->resize(0, $width);

                        $fromX = ($this->currentDimensions['width'] - $width) / 2;
                        $fromY = 0;
                        $this->crop($fromX, $fromY, $width, $height);
                    }

        return true;
    }

    public function cropResizePositioned($startX = 0, $startY = 0, $startWidth, $startHeight, $endWidth, $endHeight)
    {
        # check dimensions
        if (
            $this->currentDimensions['height'] > self::MAX_HEIGHT ||
            $this->currentDimensions['height'] < 1 ||
            $this->currentDimensions['width'] > self::MAX_WIDTH ||
            $this->currentDimensions['width'] < 1
        ) {
            return false;
        }

        if (function_exists("ImageCreateTrueColor")) {
            $this->workingImage = ImageCreateTrueColor($endWidth, $endHeight);
        } else {
            $this->workingImage = ImageCreate($endWidth, $endHeight);
        }

        $white = imagecolorallocate($this->workingImage, 255, 255, 255);
        imagefill($this->workingImage, 0, 0, $white);

        imagecopyresampled($this->workingImage, $this->newImage, 0, 0, $startX, $startY, $endWidth, $endHeight, $startWidth, $startHeight);

        $this->newImage = $this->workingImage;

        return true;
    }

    public function fillResizeCentered($width, $height, $background_image = null)
    {
        # check dimensions
        if (
            $this->currentDimensions['height'] > self::MAX_HEIGHT ||
            $this->currentDimensions['height'] < 1 ||
            $this->currentDimensions['width'] > self::MAX_WIDTH ||
            $this->currentDimensions['width'] < 1
        ) {
            return false;
        }

        // check if uploaded image is larger than the wanted one
        // if yes, we need to resize first
        if ($this->currentDimensions['height'] > $height || $this->currentDimensions['width'] > $width) {
            $this->resize($width, $height);
            /*
             if ($this->currentDimensions['height'] > $this->currentDimensions['width'])
             {
            $this->resize(0, $height);
            }
            else
            {
            $this->resize($width, 0);
            }*/
        }

        // now when the uploaded image is smaller than the wanted one we need to
        // center it on a canvas with sizes: $width x $height and save it

        // create canvas
        if ($background_image) {
            $this->workingImage = imagecreatefromjpeg($background_image);
        } else {
            if (function_exists("ImageCreateTrueColor")) {
                $this->workingImage = ImageCreateTrueColor($width, $height);
            } else {
                $this->workingImage = ImageCreate($width, $height);
            }

            $white = imagecolorallocate($this->workingImage, 255, 255, 255);
            imagefill($this->workingImage, 0, 0, $white);
        }

        $this->newDimensions['newWidth'] = isset($this->newDimensions['newWidth']) ?
            $this->newDimensions['newWidth'] :
            $this->currentDimensions['width'];

        $this->newDimensions['newHeight'] = isset($this->newDimensions['newHeight']) ?
            $this->newDimensions['newHeight'] :
            $this->currentDimensions['height'];

        // center and copy onto canvas
        imagecopy($this->workingImage, $this->newImage,
            ($width - $this->newDimensions['newWidth']) / 2,
            0,
            0, 0, $this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
        /*
         * imagecopy($this->workingImage, $this->newImage,
                 ($width - $this->newDimensions['newWidth'])/2,
                 ($height - $this->newDimensions['newHeight'])/2,
                 0, 0, $this->newDimensions['newWidth'], $this->newDimensions['newHeight']);
        */
        $this->newImage = $this->workingImage;

        return true;
    }
}

