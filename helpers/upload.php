<?php
require_once __DIR__ . '/language.php';

if (!function_exists('format_image_url')) {
    function format_image_url(?string $image): ?string {
        if (empty($image)) {
            return null;
        }
        $image = trim($image);
        if ($image === '') {
            return null;
        }

        if (str_starts_with(strtolower($image), 'http') || preg_match('/^(data:|blob:|\/\/)/i', $image)) {
            return $image;
        }

        $cleanPath = ltrim($image, '/');

        return 'https://camp-coding.online/najeh/' . $cleanPath;
    }
}

if (!function_exists('format_response_images')) {
    function format_response_images($data) {
        if (!is_array($data)) {
            if (is_string($data) && (
                str_starts_with(strtolower($data), 'http') ||
                preg_match('/^(data:|blob:|\/\/)/i', $data) ||
                str_starts_with(strtolower($data), 'uploads/') ||
                preg_match('/\.(jpg|jpeg|png|gif|webp|svg|mp4|webm|pdf|doc|docx|zip|mp3|wav)$/i', $data)
            )) {
                return format_image_url($data);
            }
            return $data;
        }

        $isAssoc = false;
        foreach (array_keys($data) as $key) {
            if (is_string($key)) {
                $isAssoc = true;
                break;
            }
        }

        if (!$isAssoc) {
            foreach ($data as $idx => $item) {
                $data[$idx] = format_response_images($item);
            }
            return $data;
        }

        $imageKeys = [
            'image', 'image_path', 'image_ar', 'image_en', 'avatar', 'photo', 'icon', 'thumbnail',
            'cover_image', 'cover', 'banner', 'banner_url'
        ];

        foreach ($imageKeys as $imgKey) {
            if (array_key_exists($imgKey, $data) && !empty($data[$imgKey]) && is_string($data[$imgKey])) {
                if (empty($data['image']) || !is_string($data['image'])) {
                    $data['image'] = $data[$imgKey];
                }
            }
        }

        if (array_key_exists('file_path', $data)) {
            if (!empty($data['file_path']) && (empty($data['file']) || !is_string($data['file']))) {
                $data['file'] = $data['file_path'];
            }
        }

        $mediaKeys = [
            'image', 'image_path', 'image_ar', 'image_en', 'avatar', 'photo', 'icon', 'thumbnail',
            'video', 'video_url', 'video_preview_url', 'video_path',
            'pdf', 'pdf_url', 'file', 'file_path', 'file_url',
            'cover_image', 'cover', 'attachment', 'attachment_url',
            'audio', 'audio_url', 'media', 'media_url',
            'certificate_file', 'certificate_path', 'certificate_url',
            'banner', 'banner_url', 'doc', 'doc_url', 'document'
        ];

        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $data[$k] = format_response_images($v);
            } elseif (is_string($v) && in_array(strtolower($k), $mediaKeys)) {
                $data[$k] = format_image_url($v);
            }
        }

        if (array_key_exists('image_path', $data) && array_key_exists('image', $data)) {
            $data['image_path'] = $data['image'];
        }
        if (array_key_exists('file_path', $data) && array_key_exists('file', $data)) {
            $data['file_path'] = $data['file'];
        }

        return $data;
    }
}

if (!function_exists('save_moved_file')) {
    function save_moved_file(array $file, string $targetFilePath): bool {
        if (isset($file['tmp_name']) && file_exists($file['tmp_name'])) {
            if (is_uploaded_file($file['tmp_name'])) {
                return move_uploaded_file($file['tmp_name'], $targetFilePath);
            } else {
                return copy($file['tmp_name'], $targetFilePath) || rename($file['tmp_name'], $targetFilePath);
            }
        }
        return false;
    }
}

if (!function_exists('upload_image_file')) {
    function upload_image_file(array $file, string $subfolder = 'avatars'): ?string {
        if (empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            return null;
        }
        if (isset($file['error']) && (int)$file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (empty($ext)) {
            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg'
            ];
            $ext = $mimeToExt[$file['type'] ?? ''] ?? '';
        }
        if ($ext === 'jpeg') $ext = 'jpg';

        // $allowedTypes above went unused and this accepted literally any file (a PDF included) as
        // long as save_moved_file() succeeded - a non-image upload must fall through to
        // upload_video_file()/upload_document_file() instead of being silently renamed img_*.<ext>.
        if (!in_array($ext, $allowedExts, true) && !in_array(strtolower($file['type'] ?? ''), $allowedTypes, true)) {
            return null;
        }

        $fileName = uniqid('img_', true) . '.' . $ext;

        $targetDir = __DIR__ . '/../uploads/' . $subfolder . '/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetFilePath = $targetDir . $fileName;
        if (save_moved_file($file, $targetFilePath)) {
            return 'uploads/' . $subfolder . '/' . $fileName;
        }

        return null;
    }
}

if (!function_exists('upload_video_file')) {
    function upload_video_file(array $file, string $subfolder = 'videos'): ?string {
        if (empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            return null;
        }
        if (isset($file['error']) && (int)$file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedExts = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (empty($ext)) {
            $mimeToExt = [
                'video/mp4' => 'mp4',
                'video/webm' => 'webm',
                'video/ogg' => 'ogg',
                'video/quicktime' => 'mov',
                'video/x-msvideo' => 'avi'
            ];
            $ext = $mimeToExt[$file['type'] ?? ''] ?? '';
        }

        // Same mistake as upload_image_file() used to make: accepting any extension here meant a
        // non-video upload never reached upload_document_file()/upload_audio_file() in the fallback
        // chain, since this function would already have "succeeded" and renamed it vid_*.<ext>.
        if (!in_array($ext, $allowedExts, true)) {
            return null;
        }

        $fileName = uniqid('vid_', true) . '.' . $ext;

        $targetDir = __DIR__ . '/../uploads/' . $subfolder . '/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetFilePath = $targetDir . $fileName;
        if (save_moved_file($file, $targetFilePath)) {
            return 'uploads/' . $subfolder . '/' . $fileName;
        }

        return null;
    }
}

if (!function_exists('upload_document_file')) {
    function upload_document_file(array $file, string $subfolder = 'documents'): ?string {
        if (empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            return null;
        }
        if (isset($file['error']) && (int)$file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip'];
        if (empty($ext) || !in_array($ext, $allowedExts)) {
            $ext = 'pdf';
        }

        $fileName = uniqid('doc_', true) . '.' . $ext;

        $targetDir = __DIR__ . '/../uploads/' . $subfolder . '/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetFilePath = $targetDir . $fileName;
        if (save_moved_file($file, $targetFilePath)) {
            return 'uploads/' . $subfolder . '/' . $fileName;
        }

        return null;
    }
}

if (!function_exists('upload_audio_file')) {
    function upload_audio_file(array $file, string $subfolder = 'audio'): ?string {
        if (empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            return null;
        }
        if (isset($file['error']) && (int)$file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowedExts = ['mp3', 'wav', 'aac', 'm4a', 'ogg', 'opus', 'amr', 'wma', 'flac', 'caf', '3gp', 'webm'];
        if (empty($ext) || !in_array($ext, $allowedExts)) {
            $mimeToExt = [
                'audio/mpeg' => 'mp3',
                'audio/mp3' => 'mp3',
                'audio/wav' => 'wav',
                'audio/x-wav' => 'wav',
                'audio/aac' => 'aac',
                'audio/mp4' => 'm4a',
                'audio/m4a' => 'm4a',
                'audio/ogg' => 'ogg',
                'audio/amr' => 'amr',
                'audio/3gpp' => '3gp',
                'audio/webm' => 'webm'
            ];
            $ext = $mimeToExt[$file['type'] ?? ''] ?? 'mp3';
        }

        $fileName = uniqid('aud_', true) . '.' . $ext;

        $targetDir = __DIR__ . '/../uploads/' . $subfolder . '/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetFilePath = $targetDir . $fileName;
        if (save_moved_file($file, $targetFilePath)) {
            return 'uploads/' . $subfolder . '/' . $fileName;
        }

        return null;
    }
}

if (!function_exists('process_image_input')) {
    function process_image_input($imageInput, string $subfolder = 'avatars', ?array $fileInput = null): ?string {
        if (is_array($fileInput) && isset($fileInput['tmp_name']) && !empty($fileInput['tmp_name'])) {
            $uploaded = upload_image_file($fileInput, $subfolder) ?? upload_audio_file($fileInput, $subfolder) ?? upload_video_file($fileInput, $subfolder) ?? upload_document_file($fileInput, $subfolder);
            if ($uploaded) return $uploaded;
        }

        if (empty($imageInput) || !is_string($imageInput)) {
            return null;
        }

        $imageInput = trim($imageInput);

        if (preg_match('/^data:(image|video|audio)\/(\w+);base64,/i', $imageInput, $matches)) {
            $mediaType = strtolower($matches[1]);
            $type = strtolower($matches[2]);
            $mediaData = substr($imageInput, strpos($imageInput, ',') + 1);
            $decodedData = base64_decode($mediaData);

            if ($decodedData === false) {
                return null;
            }

            $prefix = ($mediaType === 'video') ? 'vid_' : (($mediaType === 'audio') ? 'aud_' : 'img_');
            $ext = ($type === 'jpeg') ? 'jpg' : (($type === 'mpeg') ? 'mp3' : $type);
            $fileName = uniqid($prefix, true) . '.' . $ext;

            $targetDir = __DIR__ . '/../uploads/' . $subfolder . '/';
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $targetFilePath = $targetDir . $fileName;
            if (file_put_contents($targetFilePath, $decodedData)) {
                return 'uploads/' . $subfolder . '/' . $fileName;
            }
            return null;
        }

        return $imageInput;
    }
}
