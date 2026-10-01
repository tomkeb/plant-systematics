<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Config;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Validates uploaded images and stores resized JPEG variants (thumb / medium / orig).
 * Re-encoding through GD also strips EXIF metadata; orientation is applied first.
 */
final class PhotoService
{
    /** Lowercase letters with diacritics => plain ASCII (applied after mb_strtolower). */
    private const Diacritics = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a', 'ā' => 'a', 'ă' => 'a', 'ą' => 'a',
        'ć' => 'c', 'č' => 'c', 'ç' => 'c', 'ĉ' => 'c', 'ď' => 'd', 'đ' => 'd',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'ě' => 'e', 'ē' => 'e', 'ę' => 'e',
        'ğ' => 'g', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ı' => 'i',
        'ĺ' => 'l', 'ľ' => 'l', 'ł' => 'l', 'ń' => 'n', 'ň' => 'n', 'ñ' => 'n',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o',
        'ŕ' => 'r', 'ř' => 'r', 'ś' => 's', 'š' => 's', 'ş' => 's', 'ť' => 't', 'ţ' => 't',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ů' => 'u', 'ū' => 'u', 'ű' => 'u',
        'ý' => 'y', 'ÿ' => 'y', 'ź' => 'z', 'ž' => 'z', 'ż' => 'z',
        'ß' => 'ss', 'æ' => 'ae', 'œ' => 'oe', 'þ' => 'th',
    ];

    private readonly ImageManager $images;

    public function __construct(private readonly Config $config)
    {
        $this->images = new ImageManager(new Driver());
    }

    /**
     * @param array{name: string, tmp_name: string, error: int, size: int} $file one entry from Request::files()
     * @return string stored filename: the original name in lowercase without diacritics, unique, same in every size directory
     * @throws \RuntimeException with a user-presentable message when the upload is rejected
     */
    public function store(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Upload failed ({$file['name']}), error code {$file['error']}.");
        }
        if ($file['size'] > $this->config->get('photos.maxBytes')) {
            throw new \RuntimeException("File too large: {$file['name']}.");
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, $this->config->get('photos.allowedMime'), true)) {
            throw new \RuntimeException("Unsupported file type: {$file['name']}.");
        }

        try {
            $filename = $this->uniqueFilename($file['name']);
            $quality = $this->config->get('photos.jpegQuality');
            foreach ($this->config->get('photos.sizes') as $dir => $maxSize) {
                $image = $this->images->read($file['tmp_name'])->orient()->scaleDown($maxSize, $maxSize);
                $target = $this->directory($dir);
                $image->toJpeg($quality)->save($target . '/' . $filename);
            }
        } catch (Throwable $e) {
            if (isset($filename)) {
                $this->delete($filename);
            }
            throw new \RuntimeException("Could not process image: {$file['name']}.", 0, $e);
        }

        return $filename;
    }

    public function delete(string $filename): void
    {
        foreach (array_keys($this->config->get('photos.sizes')) as $dir) {
            $path = $this->directory($dir) . '/' . basename($filename);
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Lowercase ASCII file name derived from the original one (diacritics removed, other characters
     * turned into "-"), with "-2", "-3", ... appended when a photo of that name is already stored.
     */
    private function uniqueFilename(string $originalName): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtr(mb_strtolower(pathinfo($originalName, PATHINFO_FILENAME)), self::Diacritics)), '-');
        $base = trim(substr($base, 0, 80), '-') ?: 'photo';

        $filename = $base . '.jpg';
        for ($n = 2; $this->exists($filename); $n++) {
            $filename = "{$base}-{$n}.jpg";
        }
        return $filename;
    }

    private function exists(string $filename): bool
    {
        foreach (array_keys($this->config->get('photos.sizes')) as $dir) {
            if (is_file($this->directory($dir) . '/' . $filename)) {
                return true;
            }
        }
        return false;
    }

    private function directory(string $size): string
    {
        $dir = $this->config->get('paths.uploads') . '/' . $size;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }
}
