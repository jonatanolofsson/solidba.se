<?php
class Image {
    private $image;
    private $path;

    /**
     * Loads an image from a File class
     * @param string $path The path to the file
     */
    function __construct($path=false) {
        //$path = deutf8($path);
        $this->path = $path;
        if(!$path) return;

        /*
        switch(strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            case 'jpg':
            case 'jpeg':
                $this->image = imagecreatefromjpeg($path);
                break;
            case 'png':
                $this->image = imagecreatefrompng($path);
                break;
            case 'gif':
                $this->image = imagecreatefromgif($path);
                break;
            default:
                return false;
        }*/
        $this->image = new Imagick($path);
    }

    /**
     * retrieve a property from the image
     * @param $property
     * @return mixed
     */
    function __get($property){
        switch($property){
            case 'width':
                return $this->width();
                break;
            case 'height':
                return $this->height();
                break;
        } // switch
    }

    /**
     * Resizes the (image) file to a given size
     * @param integer $width The wished width of the image
     * @param integer $height The wished height of the image
     * @return void
     */
    function resize($width=false, $height=false) {
        if(!$width && !$height) return false;
        else {
            $w = $this->width(); $h = $this->height();
            if($width && !$height)
                $height = $h * $width / $w;
            elseif(!$width && $height)
                $width = $w * $height / $h;
        }
        if(!$width || !$height) return false;

        $this->image->resizeImage($width, $height, imagick::FILTER_CATROM, .9); //FIXME: Hard coded filter
    }

    /**
     * Crops the image to a given size
     * @param $x x-coordinate of upper left corner
     * @param $y y-coordinate of upper left corner
     * @param $width Width of the cropped image
     * @param $height Height of the cropped image
     * @return coid
     */
    function crop($x,$y,$width, $height) {
        $this->image->cropImage($width,$height,$x,$y);
        die('cropped');
    }


    /**
     * @param image &$img Image to tint
     * @param integer $tint_r Red channel
     * @param integer $tint_g Green channel
     * @param integer $tint_b Blue channel
     * @return void
     */
    function tint ($tint_r = 255, $tint_g = 255, $tint_b = 255) {
        if($tint_r === false) $tint_r = 255;
        if($tint_g === false) $tint_g = 255;
        if($tint_b === false) $tint_b = 255;
        $this->image->tintImage(new ImagickPixel("rgb($tint_r,$tint_g,$tint_b)"),new ImagickPixel('white'));
    }

    /**
     * outputs the image to a destination to either jpg, jpeg, png or gif, depending on the filename
     * @param string $destination Path where to save the file
     * @return void
     */
    function save($destination) {
        $extension = pathinfo($destination, PATHINFO_EXTENSION);
        switch(strtolower($extension)) {
            case 'png':
                $this->image->setImageFormat('PNG');
                break;
            case 'gif':
                $this->image->setImageFormat('GIF');
                break;
            default:
                $this->image->setImageFormat('JPEG');
        }
        $this->image->setImageColorspace(imagick::COLORSPACE_RGB);
        $this->image->writeImage($destination);
    }

    /**
     * Return the working copy of the image
     * @return image
     */
    function export(){
        return $this->image;
    }

    /**
     * Return width of image
     * @return integer
     */
    function width(){
        return $this->image->getImageWidth();
    }

    /**
     * Return height of image
     * @return integer
     */
    function height(){
        return $this->image->getImageHeight();
    }

    /**
     * Rotate the image an arbitrary angle
     * @param int $angle Number of degrees to rotate the image (counter-clockwise)
     * @return void
     */
    function rotate($angle){
        // Note: using cmyk(0,0,0,0) instead of 'white' because of bugs in Imagick turning 'white' and 'rgb(255,255,255)' into yellow (255,255,0) for unknown reasons...
        $this->image->rotateImage(new ImagickPixel('cmyk(0,0,0,0)'), -$angle);
    }

    /**
     * @author Kalle Karlsson [kakar]
     * Returns the EXIF embedded thumbnail from JPEG and TIFF images.
     * The image can be resize either by width and height or percentidge.
     * Returns false if no thubnail is found.
     * @param integer $width Wanted with of the image
     * @param integer $height Wanted height of the image
     * @param bool $keepPropoertions Makes the funktion return the (proportionanly) largest possible image within the $width and $height
     * @param integer $percent Proportionaly resize the by a precentidge. $percent=100 means no resize ($percent > 1)
     * @return string|bool
     */
    function thumbnail($width=false, $height=false, $keepProportions=true, $percent=false) {
        $image = exif_thumbnail($this->path, $orgWidth, $orgHeight, $type);

        if ($image!==false) {
            if($percent > 0) {
                // calculate resized height and width if percent is defined
                $percent = $percent * 0.01;
                $width = $orgWidth * $percent;
                $height = $orgHeight * $percent;
            } else {
                if($width && !$height) {
                    // autocompute height if only width is set
                    $height = (100 / ($orgWidth / $width)) * .01;
                    $height = @round($orgHeight * $height);
                } elseif($height && !$width) {
                    // autocompute width if only height is set
                    $width = (100 / ($orgHeight / $height)) * .01;
                    $width = @round ($orgWidth * $width);
                } elseif($height && $width && $keepProportions) {
                    // get the smaller resulting image dimension
                    $hx = (100 / ($orgWidth / $width)) * .01;
                    $hx = @round ($orgHeight * $hx);

                    $wx = (100 / ($orgHeight / $height)) * .01;
                    $wx = @round ($orgWidth * $wx);

                    if ($hx < $height) {
                        $height = (100 / ($orgWidth / $width)) * .01;
                        $height = @round ($orgHeight * $height);
                    } else {
                        $width = (100 / ($orgHeight / $height)) * .01;
                        $width = @round ($orgWidth * $width);
                    }
                }
            }

            return '<img width="'.$width.'" height="'.$height.'" src="data:image/gif;base64,'.base64_encode($image).'" />';
        } else {
            // no thumbnail available
            return false;
        }
    }

    /**
     * Watermark the image with an image or text (Currently broken)
     */
    function watermark($pathWatermark=false, $textWatermark=false, $size='small', $posVertical='bottom', $posHorizontal='right') {
        if(!$pathMark || !$textMark) return false;

        $watermark = imagecreatefrompng($pathWatermark);
        $watermark_width = imagesx($watermark);
        $watermark_height = imagesy($watermark);
        $dest_x = $this->width() - $watermark_width - 5;
        $dest_y = $this->height() - $watermark_height - 5;
        imagecopymerge($this->image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height, 100);
        imagedestroy($watermark);
    }

    function blob() {
        return $this->image->getImageBlob();
    }

    function __destruct() {
        $this->image->destroy();
    }

    function stream() {
        $_REQUEST->setType('action', 'string');


        $mime = getMime($this->path);
        while(ob_get_level()>0) ob_end_clean();
        //ob_start('contentSize');
        //ob_start('ob_gzhandler');

        $last_modified_time = filemtime($this->path);
        $etag = md5_file($this->path);
        $length = $filesize = filesize($this->path);
        $offset = 0;

        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time ||
                @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }

        if ( isset($_SERVER['HTTP_RANGE']) ) {
            // if the HTTP_RANGE header is set we're dealing with partial content
            // Only supports a single range right now.
            // http://stackoverflow.com/questions/157318/resumable-downloads-when-using-php-to-send-the-file
            preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $range);
            $offset = min(intval($range[1]),$filesize-1); // offset starts at 0, hence filesize-1
            if (@intval($range[2]) > 0) {
                $length = min(intval($range[2]),$filesize);
            }
            $length -= $offset;
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $filesize);
        }

        header('Etag: "'.$etag.'"');
        header('Content-Length: '.$length);
        header('Content-Disposition: '.($_REQUEST['action'] === 'download'?'attachment; ':'').'filename="'.pathinfo($this->path, PATHINFO_BASENAME).'"');
        header('Content-Transfer-Encoding: binary');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_modified_time).' GMT');
        header('Content-Type: '.$mime);

        if($_REQUEST['action'] == 'download') {
            header('Content-Type: application/force-download', false);
            header('Content-Type: application/download', false);
            header('Content-Description: File Transfer');
        }
        header('Expires: '.gmdate('D, d M Y H:i:s', time()+(2*24*3600)).' GMT');
        header('Cache-Control: private, max-age=172801, must-revalidate');
        #header('Cache-Control: no-cache');
        header('Pragma: cache');

        $fp = fopen($this->path, 'rb');
        if ($fp === false)
            die(); // error!! FIXME
        fseek($fp,$offset);
        print fread($fp,$length);
        fclose($fp);
        exit;
    }
}
?>
