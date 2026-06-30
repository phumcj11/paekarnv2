<?php
namespace App\Core;

use App\Support\ImageOptimizer;

class Upload
{
    /**
     * รับ $_FILES key, validate, save → คืน relative path (เก็บลง DB)
     */
    public static function image(string $key, string $folder = 'misc'): ?string
    {
        if (empty($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return self::imageFromEntry($_FILES[$key], $folder);
    }

    /**
     * รับแถวไฟล์เดียวจาก $_FILES (หรือจาก $_FILES['x'][$i] ที่ประกอบแล้ว)
     * error === UPLOAD_ERR_NO_FILE → null
     */
    public static function imageFromEntry(array $file, string $folder = 'misc'): ?string
    {
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload error: ' . $err);
        }

        $cfg = Application::$config['app']['upload'];

        if (($file['size'] ?? 0) > $cfg['max_size']) {
            throw new \RuntimeException('ไฟล์ใหญ่เกิน ' . round($cfg['max_size']/1024/1024) . ' MB');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('ไฟล์อัปโหลดไม่ถูกต้อง');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmp);
        if (!in_array($mime, $cfg['allow_mime'])) {
            throw new \RuntimeException('ชนิดไฟล์ไม่อนุญาต: ' . $mime);
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $cfg['allow_ext'])) {
            $ext = 'jpg';
        }

        $folder = trim($folder, '/');
        $dest   = Application::$basePath . '/public/uploads/' . $folder;
        if (!is_dir($dest)) {
            mkdir($dest, 0775, true);
        }

        $baseName = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $name     = $folder . '/' . $baseName . '.' . $ext;
        $full     = Application::$basePath . '/public/uploads/' . $name;

        if (!move_uploaded_file($tmp, $full)) {
            throw new \RuntimeException('บันทึกไฟล์ไม่ได้');
        }

        ImageOptimizer::generateVariants($name);
        self::publishRelativeToDocumentRoot($name);

        return $name;
    }

    public static function appUploadsRoot(): string
    {
        return Application::$basePath . '/public/uploads';
    }

    public static function webUploadsRoot(): ?string
    {
        $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
        if ($docRoot === '') {
            return null;
        }

        return $docRoot . '/uploads';
    }

    /** App uploads and web document-root uploads resolve to the same directory (local dev or symlink). */
    public static function uploadsAreLinked(): bool
    {
        $appRoot = self::appUploadsRoot();
        $webRoot = self::webUploadsRoot();
        if ($webRoot === null || !is_dir($appRoot)) {
            return true;
        }

        $appReal = realpath($appRoot);
        $webReal = realpath($webRoot);

        return $appReal !== false && $webReal !== false && $appReal === $webReal;
    }

    /**
     * Copy uploaded file (+ WebP variants) to DOCUMENT_ROOT/uploads when it differs from app storage.
     * Production VPS keeps app code outside public_html; without a symlink new uploads 404 until deploy.
     */
    public static function publishRelativeToDocumentRoot(string $relative): void
    {
        $relative = ltrim(trim($relative), '/');
        if ($relative === '' || str_starts_with($relative, 'http')) {
            return;
        }
        if (self::uploadsAreLinked()) {
            return;
        }

        $webRoot = self::webUploadsRoot();
        if ($webRoot === null) {
            return;
        }

        ['dir' => $dir, 'base' => $base] = ImageOptimizer::splitBase($relative);
        $appDir = self::appUploadsRoot() . ($dir !== '' ? '/' . $dir : '');
        if (!is_dir($appDir)) {
            return;
        }

        $webDir = $webRoot . ($dir !== '' ? '/' . $dir : '');
        if (!is_dir($webDir) && !@mkdir($webDir, 0775, true) && !is_dir($webDir)) {
            return;
        }

        foreach (glob($appDir . '/' . $base . '*') ?: [] as $src) {
            if (!is_file($src)) {
                continue;
            }
            $dest = $webDir . '/' . basename($src);
            if (!is_file($dest) || filemtime($src) > @filemtime($dest)) {
                @copy($src, $dest);
            }
        }
    }

    /** Remove a relative upload path from app storage (and web mirror when split). */
    public static function deleteRelative(?string $relative): void
    {
        $relative = ltrim(trim((string) $relative), '/');
        if ($relative === '' || str_starts_with($relative, 'http')) {
            return;
        }

        ['dir' => $dir, 'base' => $base] = ImageOptimizer::splitBase($relative);
        foreach ([self::appUploadsRoot(), self::webUploadsRoot()] as $root) {
            if ($root === null || !is_dir($root)) {
                continue;
            }
            $dirFull = $root . ($dir !== '' ? '/' . $dir : '');
            foreach (glob($dirFull . '/' . $base . '*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    /** @deprecated Use ImageOptimizer::variantRelativePath($path, 'thumb') */
    public static function thumbPath(string $path): string
    {
        return ImageOptimizer::variantRelativePath($path, 'thumb');
    }

    /**
     * แปลง $_FILES['key'][] (multiple upload) เป็นรายการแถวไฟล์สำหรับ imageFromEntry
     *
     * @return list<array{name:string,type:string,tmp_name:string,error:int,size:int}>
     */
    public static function normalizeIndexedFiles(string $key): array
    {
        if (empty($_FILES[$key]) || !isset($_FILES[$key]['name']) || !is_array($_FILES[$key]['name'])) {
            return [];
        }

        $out = [];
        foreach ($_FILES[$key]['name'] as $i => $_name) {
            $out[] = [
                'name'     => (string)($_FILES[$key]['name'][$i] ?? ''),
                'type'     => (string)($_FILES[$key]['type'][$i] ?? ''),
                'tmp_name' => (string)($_FILES[$key]['tmp_name'][$i] ?? ''),
                'error'    => (int)($_FILES[$key]['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size'     => (int)($_FILES[$key]['size'][$i] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * รับไฟล์จาก input เดียวหรือ multiple (name="image[]" multiple)
     *
     * @return list<array{name:string,type:string,tmp_name:string,error:int,size:int}>
     */
    public static function galleryUploadEntries(string $key): array
    {
        $entries = self::normalizeIndexedFiles($key);
        if ($entries !== []) {
            return $entries;
        }
        if (!empty($_FILES[$key]) && !is_array($_FILES[$key]['name'] ?? null)) {
            return [$_FILES[$key]];
        }

        return [];
    }

    /**
     * อัปโหลดหลายรูปจาก request key เดียว
     *
     * @return array{0:list<string>,1:list<string>} paths, errors
     */
    public static function imagesFromRequest(string $key, string $folder = 'misc'): array
    {
        $paths = [];
        $errors = [];
        foreach (self::galleryUploadEntries($key) as $file) {
            try {
                $path = self::imageFromEntry($file, $folder);
                if ($path !== null) {
                    $paths[] = $path;
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return [$paths, $errors];
    }
}
