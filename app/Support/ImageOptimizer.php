<?php

namespace App\Support;

use App\Core\Application;

/**
 * สร้างและเลือก WebP variants สำหรับรูปใน uploads/
 *
 * Variants (relative to uploads/):
 *   original  — ย่อ/บีบอัดไม่เกิน 1600px
 *   *_md.webp — 800px  ใช้ Hero / Banner / LCP
 *   *_thumb.webp — 400px ใช้การ์ดรายการ
 */
class ImageOptimizer
{
    public const WIDTH_LG    = 1600;
    public const WIDTH_MD    = 800;
    public const WIDTH_THUMB = 400;

    private const REENCODE_MIN_BYTES = 180_000;

    private static function uploadsRoot(): string
    {
        return Application::$basePath . '/public/uploads';
    }

    public static function fullPath(string $relative): string
    {
        return self::uploadsRoot() . '/' . ltrim($relative, '/');
    }

    public static function isProcessableMime(string $mime): bool
    {
        return in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    public static function isVariantRelativePath(string $path): bool
    {
        return (bool) preg_match('/_(?:thumb|md)\.webp$/i', $path);
    }

    /** @return array{dir:string,base:string} */
    public static function splitBase(string $relative): array
    {
        $relative = ltrim($relative, '/');
        $dir      = dirname($relative);
        if ($dir === '.') {
            $dir = '';
        }
        $filename = pathinfo($relative, PATHINFO_FILENAME);
        $base     = preg_replace('/_(?:thumb|md)$/', '', $filename) ?? $filename;

        return ['dir' => $dir, 'base' => $base];
    }

    public static function variantRelativePath(string $relative, string $size = 'md'): string
    {
        $relative = trim($relative);
        if ($relative === '' || preg_match('#^https?://#i', $relative)) {
            return $relative;
        }

        if (in_array($size, ['original', 'orig', 'full'], true)) {
            return $relative;
        }

        $suffix = match ($size) {
            'thumb', 'card' => '_thumb.webp',
            default         => '_md.webp',
        };

        ['dir' => $dir, 'base' => $base] = self::splitBase($relative);
        $variant = ($dir !== '' ? $dir . '/' : '') . $base . $suffix;

        return is_file(self::fullPath($variant)) ? $variant : $relative;
    }

    public static function variantUrl(string $relative, string $size = 'md'): string
    {
        $relative = trim($relative);
        if ($relative === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $relative)) {
            return $relative;
        }

        return upload_url(self::variantRelativePath($relative, $size));
    }

    /**
     * สร้าง/อัปเดต variants จากไฟล์ต้นฉบับใน uploads (relative path)
     */
    public static function generateVariants(string $relative): bool
    {
        $relative = ltrim(trim($relative), '/');
        if ($relative === '' || self::isVariantRelativePath($relative)) {
            return false;
        }

        $full = self::fullPath($relative);
        if (!is_file($full)) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($full);
        if (!self::isProcessableMime($mime)) {
            return false;
        }

        ['dir' => $dir, 'base' => $base] = self::splitBase($relative);
        $dirFull = self::uploadsRoot() . ($dir !== '' ? '/' . $dir : '');

        $mdPath    = $dirFull . '/' . $base . '_md.webp';
        $thumbPath = $dirFull . '/' . $base . '_thumb.webp';

        // บีบอัดต้นฉบับ (ย่อถ้าเกิน 1600px หรือไฟล์ใหญ่เกินเกณฑ์)
        $forceOrig = filesize($full) > self::REENCODE_MIN_BYTES;
        self::saveVariant($full, $full, self::WIDTH_LG, $mime, $mime, $forceOrig);

        self::saveVariant($full, $mdPath, self::WIDTH_MD, $mime, 'image/webp');
        self::saveVariant($full, $thumbPath, self::WIDTH_THUMB, $mime, 'image/webp');

        return is_file($mdPath) && is_file($thumbPath);
    }

    /**
     * นับรูปต้นฉบับและที่ยังไม่มี WebP variant
     *
     * @return array{originals:int,has_md:int,missing_md:int}
     */
    public static function scanStats(): array
    {
        $root  = self::uploadsRoot();
        $stats = ['originals' => 0, 'has_md' => 0, 'missing_md' => 0];

        if (!is_dir($root)) {
            return $stats;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if (self::isVariantRelativePath($relative)) {
                continue;
            }

            $stats['originals']++;
            $mdRel = self::variantRelativePath($relative, 'md');
            if ($mdRel !== $relative) {
                $stats['has_md']++;
            } else {
                $stats['missing_md']++;
            }
        }

        return $stats;
    }

    /**
     * สแกน uploads/ แล้วสร้าง variants ให้รูปเดิมทั้งหมด
     *
     * @return array{ok:int,skip:int,fail:int,total:int}
     */
    public static function regenerateAll(?callable $onProgress = null): array
    {
        $root  = self::uploadsRoot();
        $stats = ['ok' => 0, 'skip' => 0, 'fail' => 0, 'total' => 0];

        if (!is_dir($root)) {
            return $stats;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            if (self::isVariantRelativePath($relative)) {
                $stats['skip']++;
                continue;
            }

            $stats['total']++;
            if ($onProgress) {
                $onProgress($relative);
            }

            if (self::generateVariants($relative)) {
                $stats['ok']++;
            } else {
                $stats['fail']++;
            }
        }

        return $stats;
    }

    private static function saveVariant(
        string $src,
        string $dest,
        int $maxWidth,
        string $srcMime,
        string $destMime,
        bool $force = false
    ): bool {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }

        try {
            [$origW, $origH] = getimagesize($src);
            if (!$origW || !$origH) {
                return false;
            }

            $needsResize = $origW > $maxWidth;
            if (!$needsResize && !$force && $destMime === $srcMime && $src === $dest) {
                return true;
            }

            $scale = $needsResize ? $maxWidth / $origW : 1.0;
            $newW  = max(1, (int) round($origW * $scale));
            $newH  = max(1, (int) round($origH * $scale));

            $srcImg = match ($srcMime) {
                'image/jpeg' => @imagecreatefromjpeg($src),
                'image/png'  => @imagecreatefrompng($src),
                'image/webp' => @imagecreatefromwebp($src),
                default      => false,
            };
            if ($srcImg === false) {
                return false;
            }

            $dstImg = imagecreatetruecolor($newW, $newH);
            if ($destMime === 'image/webp' || $destMime === 'image/png') {
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
                $transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
                imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);
            }

            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($srcImg);

            $ok = match ($destMime) {
                'image/webp' => imagewebp($dstImg, $dest, 82),
                'image/jpeg' => imagejpeg($dstImg, $dest, 82),
                'image/png'  => imagepng($dstImg, $dest, 8),
                default      => false,
            };

            imagedestroy($dstImg);

            return (bool) $ok;
        } catch (\Throwable) {
            return false;
        }
    }
}
