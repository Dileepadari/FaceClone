<?php
namespace App\Core;

/**
 * Image and video upload handling. Images are re-encoded through GD, which both
 * normalises orientation/size and strips anything that is not actually an image.
 */
final class Upload
{
    public const AVATAR = 'avatars';
    public const COVER  = 'covers';
    public const POST   = 'posts';
    public const STORY  = 'stories';
    public const MSG    = 'messages';
    public const GROUP  = 'groups';

    /** Longest-edge caps per destination folder. */
    private const MAX_EDGE = [
        self::AVATAR => 720,
        self::COVER  => 1600,
        self::POST   => 1600,
        self::STORY  => 1280,
        self::MSG    => 1280,
        self::GROUP  => 1600,
    ];

    public static function lastError(): string
    {
        return self::$error;
    }

    private static string $error = '';

    /**
     * Store one uploaded image. Returns the public path (e.g. /uploads/posts/ab12.jpg)
     * or null with lastError() set.
     */
    public static function image(array $file, string $folder): ?string
    {
        self::$error = '';
        if (!self::validate($file, App::config('uploads.image_mime', []))) {
            return null;
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            self::$error = 'That file is not a readable image.';
            return null;
        }

        [$width, $height] = $info;
        $mime = $info['mime'];

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/gif'  => @imagecreatefromgif($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
            default      => false,
        };
        if (!$source) {
            self::$error = 'That image could not be processed.';
            return null;
        }

        $source = self::applyExifOrientation($source, $file['tmp_name'], $mime, $width, $height);

        $maxEdge = self::MAX_EDGE[$folder] ?? 1600;
        $scale   = min(1, $maxEdge / max($width, $height));
        $newW    = max(1, (int) round($width * $scale));
        $newH    = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newW, $newH);
        // Preserve transparency for PNG/GIF/WebP sources.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($source);

        $keepAlpha = in_array($mime, ['image/png', 'image/gif', 'image/webp'], true);
        $ext       = $keepAlpha ? 'png' : 'jpg';
        $name      = bin2hex(random_bytes(10)) . '.' . $ext;
        $dir       = App::basePath('public/uploads/' . $folder);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            self::$error = 'Upload folder is not writable.';
            imagedestroy($canvas);
            return null;
        }

        $ok = $keepAlpha
            ? imagepng($canvas, "$dir/$name", 6)
            : imagejpeg($canvas, "$dir/$name", 86);
        imagedestroy($canvas);

        if (!$ok) {
            self::$error = 'Could not save the processed image.';
            return null;
        }

        return "/uploads/$folder/$name";
    }

    /** Store an uploaded video without re-encoding. */
    public static function video(array $file, string $folder): ?string
    {
        self::$error = '';
        if (!self::validate($file, App::config('uploads.video_mime', []))) {
            return null;
        }
        $ext  = match (self::detectMime($file['tmp_name'])) {
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/quicktime' => 'mov',
            default           => 'mp4',
        };
        $name = bin2hex(random_bytes(10)) . '.' . $ext;
        $dir  = App::basePath('public/uploads/' . $folder);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            self::$error = 'Upload folder is not writable.';
            return null;
        }
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name") && !rename($file['tmp_name'], "$dir/$name")) {
            self::$error = 'Could not save the video.';
            return null;
        }
        return "/uploads/$folder/$name";
    }

    /** Route an upload to image() or video() based on its real MIME type. */
    public static function media(array $file, string $folder): ?array
    {
        $mime = self::detectMime($file['tmp_name']);
        if (in_array($mime, App::config('uploads.video_mime', []), true)) {
            $path = self::video($file, $folder);
            return $path ? ['path' => $path, 'type' => 'video'] : null;
        }
        $path = self::image($file, $folder);
        return $path ? ['path' => $path, 'type' => 'image'] : null;
    }

    /** Remove a previously stored upload. Ignores anything outside public/uploads. */
    public static function delete(?string $publicPath): void
    {
        if (!$publicPath || !str_starts_with($publicPath, '/uploads/')) {
            return;
        }
        $file = App::basePath('public' . $publicPath);
        $real = realpath($file);
        $root = realpath(App::basePath('public/uploads'));
        if ($real && $root && str_starts_with($real, $root) && is_file($real)) {
            @unlink($real);
        }
    }

    private static function validate(array $file, array $allowedMime): bool
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            self::$error = match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is too large.',
                UPLOAD_ERR_PARTIAL                        => 'The upload was interrupted. Try again.',
                default                                   => 'The upload failed. Try again.',
            };
            return false;
        }
        $max = (int) App::config('uploads.max_bytes', 8 * 1024 * 1024);
        if ($file['size'] > $max) {
            self::$error = 'That file is larger than ' . round($max / 1048576) . ' MB.';
            return false;
        }
        $mime = self::detectMime($file['tmp_name']);
        if (!in_array($mime, $allowedMime, true)) {
            self::$error = 'That file type is not supported.';
            return false;
        }
        return true;
    }

    private static function detectMime(string $path): string
    {
        if (!is_file($path)) {
            return '';
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return (string) $finfo->file($path);
    }

    private static function applyExifOrientation(\GdImage $image, string $path, string $mime, int &$width, int &$height): \GdImage
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($orientation) {
            3       => imagerotate($image, 180, 0),
            6       => imagerotate($image, -90, 0),
            8       => imagerotate($image, 90, 0),
            default => null,
        };
        if (!$rotated) {
            return $image;
        }
        imagedestroy($image);
        $width  = imagesx($rotated);
        $height = imagesy($rotated);
        return $rotated;
    }
}
