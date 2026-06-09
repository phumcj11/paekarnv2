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

        return $name;
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
}
