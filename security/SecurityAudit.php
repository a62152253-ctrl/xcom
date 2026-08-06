<?php
namespace Security;

class SecurityAudit {
    public static function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 5242880) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return false;
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return false;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return false;
            default:
                return false;
        }

        if ($file['size'] > $maxSize) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if (false === $ext = array_search(
            $finfo->file($file['tmp_name']),
            $allowedTypes,
            true
        )) {
            return false;
        }

        return true;
    }
}
