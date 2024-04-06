<?php
namespace carry0987\Helper;

use carry0987\Image\Image as Image;
use carry0987\Sharp\Sharp;

class ImageHelper extends Helper
{
    private $target_path = null;

    const SHARP_API = 'Sharp';
    const IS_COVER = 'cover';
    const IS_THUMBNAIL = 'thumb';
    private const ALBUM_INDEX = 'album_id';

    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            parent::__construct($connect_db);
        }

        // Set param
        $param = array(
            'field_name' => 'image',
            'allow_type' => array(),
            'image_quality' => 95,
            'image_library' => Image::LIBRARY_GD,
            'get_thumbnail' => false,
            'root_path' => null,
            'thumbnail_height' => 0,
            'thumbnail_width' => 200,
            'save_by_date' => false,
            'datetime' => null,
            'filename' => null,
            'random_name' => false,
            'suffix' => null,
            'remove_origin' => false,
            'crop_square' => false,
            'format' => null,
            'record_type' => null
        );

        // Merge param
        self::setParam($param);
    }

    public function createTest(array $data, array $file): bool|array
    {
        if (empty($file)) return false;
        if (!isset($data['image_library'])) return false;
        if (self::setImageLibrary($data['image_library']) === false) return false;

        // Upload image
        $image_info = $this->getImageUpload($file);

        // Check image upload
        if ($image_info === null) return false;

        // Create thumbnail
        if (self::$param['get_thumbnail'] === true) {
            $thumbnail_path = $this->createThumbnail($image_info, $image_info['path']);
            $image_info['thumbnail'] = $thumbnail_path;
            $image_info['height'] = self::$param['thumbnail_height'];
            $image_info['width'] = self::$param['thumbnail_width'];
        }

        return $image_info;
    }

    public function createRecord(array $data, array $file, int $user_id): bool|array
    {
        if (empty($data[self::ALBUM_INDEX]) || empty($file)) return false;

        // Upload image
        $image_info = $this->getImageUpload($file);

        // Check image upload
        if ($image_info === null) return false;

        // Create image info into Database
        $create['random_name'] = str_replace('.'.$image_info['file_ext'], '', $image_info['file_name']);
        $create['file_name'] = $image_info['origin_name'];
        $create['user_id'] = $user_id;
        $create[self::ALBUM_INDEX] = (ctype_digit($data[self::ALBUM_INDEX])) ? (int) $data[self::ALBUM_INDEX] : null;
        $create['file_path'] = $image_info['path'];
        $create['file_type'] = $image_info['file_ext'];
        $create['file_size'] = $image_info['file_size'];
        $create['md5'] = null;
        $create['post_date'] = time();
        $create['created_date'] = substr($data['lastModified'], 0, -3);
        $create['last_modified'] = substr($data['lastModified'], 0, -3);
        $result = parent::$dataCreate->createImage($create);

        return $result;
    }

    public function createThumbnailFromSource(string $source_path): array|bool
    {
        // Get image info
        $image_info = $this->getImageLocal($source_path);

        // Check image upload
        if ($image_info === null) return false;

        // Create thumbnail
        $thumbnail_path = $this->createThumbnail($image_info, $image_info['path']);

        // Check thumbnail
        if ($thumbnail_path === null) return false;

        // Thumbnail info
        $thumbnail = array();
        $thumbnail['short_path'] = $thumbnail_path;
        $thumbnail['full_path'] = Utils::trimPath(self::$param['root_path'].'/'.$thumbnail_path);

        return $thumbnail;
    }

    public function createThumbnailRecord(array $data): bool
    {
        $result = false;
        $path = self::getThumbnailPath(false);
        $path = rtrim($path, self::$param['suffix'].'.'.self::$param['format']);
        $path = Utils::trimPath($data['dir'].'/'.$path);
        // Create record of cover
        if (self::$param['record_type'] === self::IS_COVER) {
            // Create cover info
            $cover_info = array();
            $cover_info[self::ALBUM_INDEX] = $data[self::ALBUM_INDEX];
            $cover_info['image_id'] = $data['image_id'];
            $cover_info['file_path'] = $path;
            $cover_info['last_edit'] = $cover_info['post_date'] = time();
            // Create cover record
            $result = parent::$dataCreate->createCover($cover_info);
        }
        // Create record of thumbnail
        if (self::$param['record_type'] === self::IS_THUMBNAIL) {
            // Create thumbnail info
            $thumb_info = array();
            $thumb_info['image_id'] = $data['image_id'];
            $thumb_info['file_path'] = $path;
            $thumb_info['file_type'] = self::$param['format'];
            $thumb_info['created_date'] = $thumb_info['last_modified'] = time();
            // Create thumbnail record
            $result = parent::$dataCreate->createThumb($thumb_info);
        }

        return $result;
    }

    public static function setImageLibrary($value = Image::LIBRARY_GD)
    {
        switch ($value) {
            case Image::LIBRARY_GD:
                if (function_exists('gd_info')) {
                    self::$param['image_library'] = Image::LIBRARY_GD;
                }
                break;
            case Image::LIBRARY_IMAGICK:
                if (extension_loaded('imagick') || class_exists('Imagick')) {
                    self::$param['image_library'] = Image::LIBRARY_IMAGICK;
                }
                break;
            case self::SHARP_API:
                self::$param['image_library'] = self::SHARP_API;
                break;
            default:
                return false;
        }

        return self::$param['image_library'];
    }

    public static function setSize(int $height = 0, int $width = 200): void
    {
        self::$param['thumbnail_height'] = $height;
        self::$param['thumbnail_width'] = $width;
    }

    public static function setSuffix(?string $suffix): void
    {
        self::$param['suffix'] = $suffix;
    }

    public static function getThumbnailPath(bool $full_path = true): string
    {
        $path = $full_path ? self::$param['root_path'].'/' : null;
        if (self::$param['save_by_date'] === true) {
            $path .= Utils::getPathByDate(self::$param['datetime']).'/';
        }
        $path .= self::getImageName(self::$param['filename']).'.'.self::$param['format'];

        return Utils::trimPath($path);
    }

    public static function generateSharpURL(string $image_path): string|bool
    {
        // Check sharp config
        if (!Utils::checkEmpty(self::$param, array('sharpServer', 'signatureKey', 'signatureSalt', 'sourceKey', 'sharpDirectory'))) return false;

        return self::createWithSharp($image_path);
    }

    private function getImageUpload(array $getFile): ?array
    {
        // Folder path setup
        $this->target_path = Utils::trimPath(self::$param['root_path'].'/');
        if (self::$param['save_by_date'] === true) {
            $this->target_path = Utils::trimPath($this->target_path.Utils::getPathByDate(self::$param['datetime']));
        }

        // Check directory making
        if (!Utils::makePath($this->target_path)) return null;

        // Check if multiple file
        if (self::isMultipleFile($getFile)) return null;

        // File name setup
        $origin_name = $getFile[self::$param['field_name']]['name'];
        ['file_name' => $file_name, 'file_ext' => $file_ext] = self::setupFileName($origin_name);

        // Set file name
        $image_file = Utils::trimPath($this->target_path.'/'.basename($file_name));

        // Start move uploaded image
        $file = array();
        $file['origin_name'] = $origin_name;
        $file['file_name'] = $file_name;
        $file['tmp_name'] = $getFile[self::$param['field_name']]['tmp_name'];
        if (!self::moveUploadedImage($file, $image_file)) return null;
        $file['file_ext'] = $file_ext;
        $file['file_size'] = $getFile[self::$param['field_name']]['size'];
        $file['path'] = $image_file;

        return $file;
    }

    private function getImageLocal(string $image_path): ?array
    {
        // Check image path
        if (!file_exists($image_path)) return null;

        // Filter file name
        ['file_name' => $file_name, 'file_ext' => $file_ext] = self::setupFileName($image_path);

        // Get image info
        $image_info = array();

        // Set image info
        $image_info['origin_name'] = basename($image_path);
        $image_info['file_name'] = $file_name;
        $image_info['file_ext'] = $file_ext;
        $image_info['file_size'] = filesize($image_path);
        $image_info['path'] = $image_path;

        return $image_info;
    }

    private function createThumbnail(array $image_info, string $source_image): ?string
    {
        // Set thumbnail name
        $thumbnail_name = self::getImageName(str_replace('.'.$image_info['file_ext'], '', $image_info['file_name']));
        $thumbnail_name .= '.'.$image_info['file_ext'];

        // Set output file extension
        $target_image = Utils::trimPath(self::$param['root_path'].'/'.$thumbnail_name);
        switch (self::$param['image_library']) {
            case Image::LIBRARY_GD:
                //Create with GD
                $created_path = self::createWithGD($source_image, $target_image);
                break;
            case Image::LIBRARY_IMAGICK:
                //Create with Imagick
                $created_path = self::createWithImagick($source_image, $target_image);
                break;
            case self::SHARP_API:
                //Create with Sharp API
                $created_path = self::createWithSharp($source_image);
                break;
            default:
                $created_path = null;
                break;
        }

        return $created_path;
    }

    private static function createWithImagick(string $source_path, string $thumbnail_path): ?string
    {
        $image = new Image($source_path, Image::LIBRARY_IMAGICK);

        return self::startProcess($image, $thumbnail_path, $source_path);
    }

    private static function createWithGD(string $source_path, string $thumbnail_path): ?string
    {
        $image = new Image($source_path, Image::LIBRARY_GD);

        return self::startProcess($image, $thumbnail_path, $source_path);
    }

    private static function createWithSharp(string $source_path): string
    {
        $sharpServer = self::$param['sharpServer'];
        $signatureKey = self::$param['signatureKey'];
        $signatureSalt = self::$param['signatureSalt'];
        $sourceKey = self::$param['sourceKey'];
        $sharpDirectory = self::$param['sharpDirectory'];

        // Create Sharp API
        $sharp = new Sharp($signatureKey, $signatureSalt, $sourceKey);

        // Set image path
        $image_file = str_replace($sharpDirectory, '', $source_path);

        // Set image size
        if (self::$param['crop_square'] === true) {
            $sharp->setWidth(self::$param['thumbnail_width'])->setHeight(self::$param['thumbnail_width'])->setSuffix('_sq');
        } else {
            $sharp->setWidth(self::$param['thumbnail_width'])->setSuffix(self::$param['suffix']);
        }

        // Set image format
        if (self::$param['format'] !== null) {
            $sharp->setFormat(self::$param['format']);
        }

        // Generate signed URL
        $signedUrl = $sharp->generateEncryptedUrl($image_file);

        return $sharpServer.$signedUrl;
    }

    private function isMultipleFile(array $file_array): bool
    {
        // Check is file or image
        self::$param['field_name'] = (isset($file_array['file'])) ? 'file' : self::$param['field_name'];

        // Check if multiple file
        if (isset($file_array[self::$param['field_name']]['name'])) {
            if (is_array($file_array[self::$param['field_name']]['name'])) {
                return true;
            }
        }

        return false;
    }

    private static function getImageName(string $filename): string
    {
        $suffix = (self::$param['suffix'] !== null) ? self::$param['suffix'] : '';

        return $filename.$suffix;
    }

    private static function setupFileName(string $image_path): array
    {
        $origin_name = basename($image_path);
        $file_ext = Image::getFileExtension($origin_name);
        $file_ext = ($file_ext === 'jpeg') ? 'jpg' : $file_ext;

        // Filter file name
        $file_name = str_replace(' ', '_', $origin_name);
        $file_name = ltrim($file_name, '.');

        // Set file name by condition
        if (self::$param['random_name']) {
            $random_name = Utils::generateRandom(8).'.'.$file_ext;
            $file_name = $random_name;
        } elseif (self::$param['filename'] !== null) {
            $specific_filename = self::$param['filename'].'.'.$file_ext;
            $file_name = $specific_filename;
        }

        return ['file_name' => $file_name, 'file_ext' => $file_ext];
    }

    private static function moveUploadedImage(array $file_array, string $target_path)
    {
        if (!isset($file_array['tmp_name'])) return false;

        if (move_uploaded_file($file_array['tmp_name'], $target_path)) {
            return true;
        }

        return false;
    }

    private static function startProcess(Image $image, string $thumbnail_path, string $image_file): ?string
    {
        if (!empty(self::$param['allow_type'])) {
            $image->setAllowType(self::$param['allow_type']);
        }
        $image->setRootPath(self::$param['root_path']);
        $image->startProcess()->setCompressionQuality(self::$param['image_quality']);

        // Save by date
        if (self::$param['save_by_date'] === true) {
            $image->saveByDate(self::$param['datetime']);
        }

        // Set image size
        if (self::$param['crop_square'] === true) {
            $image->cropSquare(self::$param['thumbnail_width']);
        } else {
            $image->resizeImage(self::$param['thumbnail_width'], self::$param['thumbnail_height']);
        }

        // Set image format
        if (self::$param['format'] !== null) {
            $image->setFormat(self::$param['format']);
        }

        // Start write image
        $image->writeImage($thumbnail_path);
        $image->destroyImage();

        // Remove original file
        self::removeOriginalFile($image_file);

        return $image->getCreatedPath();
    }

    private static function removeOriginalFile(string $image_file): bool
    {
        if (self::$param['remove_origin'] === true && file_exists($image_file)) {
            return unlink($image_file);
        }

        return true;
    }
}
