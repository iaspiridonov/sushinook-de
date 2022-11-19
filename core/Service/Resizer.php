<?php namespace Core\Service;

class Resizer {
    CONST PUBLIC_PATH = 'public';

    public static function resize($image, $width, $height, $proportion = false) {
        $newImagePath = $width.'x'.$height.DIRECTORY_SEPARATOR.$image;
        $fileName = explode("/", $image);
        $fileName = end($fileName);

        if(file_exists(self::PUBLIC_PATH.'/uploads'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$width.'x'.$height.DIRECTORY_SEPARATOR.$fileName)) {
            return '/uploads/images/'.$width.'x'.$height.'/'.$fileName;
        }
        $newImageName = self::save($image, $width, $height, $proportion);
        return $newImageName;
    }

    protected static function save($path, $pwidth, $pheight, $proportion) {
        $dir = $pwidth.'x'.$pheight;
        if(!is_dir(SELF::PUBLIC_PATH.'/uploads'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$dir))
            mkdir(SELF::PUBLIC_PATH.'/uploads'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$dir);

        $imagePath = $path;
        list($width, $height) = getimagesize($imagePath);
        $newwidth = $pwidth;
        $newheight = $pheight;
        $x = 0;
        $y = 0;

        if($proportion) {
            $ratio = $height / $width;
            if($width>$height) {
                $newheight = $newwidth * $ratio;
            } else {
                $ratio = $width / $height;
                $newwidth = $newheight * $ratio;
            }
        } else {
            if($width > $height) {
                $x = ceil(($width - $height) / 2 );
                $width = $height;
            } elseif($height > $width) {
                $y = ceil(($height - $width) / 2);
                $height = $width;
            }
        }
        $thumb = imagecreatetruecolor($newwidth, $newheight);
        imagesavealpha($thumb, true);

        $trans_colour = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $trans_colour);

        //$red = imagecolorallocate($thumb, 255, 0, 0);
        //imagefilledellipse($thumb, $newwidth, $newheight, $newwidth, $newheight,$red);
        $source = self::imagecreatefromfile($imagePath);
        imagecopyresampled($thumb, $source, 0, 0, $x, $y, $newwidth, $newheight, $width, $height);

        //////////////////////////
        /// /////////////////////



        $fileName = explode("/", $path);
        $fileName = end($fileName);
        self::saveImage($thumb, $dir.'/'.$fileName, $imagePath);
        return '/uploads'.'/images/'.$pwidth.'x'.$pheight.'/'.$fileName;
    }

    public static function saveImage($thumb, $newFileName, $filename ) {
        switch ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ))) {
            case 'jpeg':
            case 'jpg':
                $newFileName = '/uploads/images/'.$newFileName;
                imagejpeg($thumb ,'public'.$newFileName,100);
                break;

            case 'png':
            case 'gif':
                $newFileName = '/uploads/images/'.$newFileName;
                imagepng($thumb ,'public'.$newFileName,9);
                break;

            default:
                throw new InvalidArgumentException('File "'.$filename.'" is not valid jpg, png or gif image.');
                break;
        }
        return $newFileName;
    }

    public static function imagecreatefromfile( $filename ) {
        //if (!file_exists($filename)) {
        //    throw new InvalidArgumentException('File "'.$filename.'" not found.');
        //}
        switch ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ))) {
            case 'jpeg':
            case 'jpg':
                return imagecreatefromjpeg($filename);
                break;

            case 'png':
                return imagecreatefrompng($filename);
                break;

            case 'gif':
                return imagecreatefromgif($filename);
                break;

            default:
                throw new InvalidArgumentException('File "'.$filename.'" is not valid jpg, png or gif image.');
                break;
        }
    }
}
?>