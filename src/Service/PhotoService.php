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
    private readonly ImageManager $images;

    public function __construct(private readonly Config $config)
    {
        $this->images = new ImageManager(new Driver());
    }

    /**
     * @param array{name: string, tmp_name: string, error: int, size: int} $file one entry from Request::files()
     * @return string stored filename (same name in every size directory)
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
            $filename = bin2hex(random_bytes(8)) . '.jpg';
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

    private function directory(string $size): string
    {
        $dir = $this->config->get('paths.uploads') . '/' . $size;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }
}
