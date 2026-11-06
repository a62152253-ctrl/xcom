<?php
class FileUploader {
    public static function upload($file_info, $destination_dir, $allowed_types = ['image/jpeg', 'image/png', 'image/webp'], $max_size = 2097152) {
        if (!isset($file_info['error']) || is_array($file_info['error'])) {
            throw new RuntimeException('Invalid parameters.');
        }

        if ($file_info['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload error: ' . $file_info['error']);
        }

        if ($file_info['size'] > $max_size) {
            throw new RuntimeException('Exceeded filesize limit.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file_info['tmp_name']);

        if (!in_array($mime, $allowed_types, true)) {
            throw new RuntimeException('Invalid file format.');
        }

        $ext = array_search($mime, [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ], true);

        if ($ext === false) {
            throw new RuntimeException('Invalid file format.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = rtrim($destination_dir, '/') . '/' . $filename;

        if (!move_uploaded_file($file_info['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        return $filename;
    }
}
