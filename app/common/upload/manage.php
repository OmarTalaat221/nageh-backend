<?php
require_once __DIR__ . '/../../../helpers/functions.php';
require_once __DIR__ . '/../../../helpers/language.php';
require_once __DIR__ . '/../../../helpers/upload.php';

if (!function_exists('handleAudioFileUpload')) {
    function handleAudioFileUpload($con = null) {
        $body = read_json_body();
        $fileInput = null;

        $possibleFileKeys = ['file', 'audio', 'record', 'voice', 'sound', 'attachment', 'media'];
        foreach ($possibleFileKeys as $key) {
            if (!empty($_FILES[$key]) && is_array($_FILES[$key]) && !empty($_FILES[$key]['tmp_name'])) {
                $fileInput = $_FILES[$key];
                break;
            }
        }

        $audioPath = null;
        if ($fileInput) {
            $audioPath = upload_audio_file($fileInput, 'audio');
        }

        if (!$audioPath) {
            $base64Input = null;
            foreach ($possibleFileKeys as $key) {
                if (!empty($body[$key]) && is_string($body[$key])) {
                    $base64Input = $body[$key];
                    break;
                }
            }
            if ($base64Input) {
                $audioPath = process_image_input($base64Input, 'audio');
            }
        }

        if (empty($audioPath)) {
            return ['status' => 'error', 'message' => trans('audio_file_required')];
        }

        $fullUrl = format_image_url($audioPath);

        return [
            'status' => 'success',
            'message' => trans('audio_upload_success'),
            'data' => [
                'file_path' => $audioPath,
                'file_url' => $fullUrl,
                'audio_url' => $fullUrl,
                'link' => $fullUrl
            ]
        ];
    }
}

if (!function_exists('handleGeneralFileUpload')) {
    function handleGeneralFileUpload($con = null) {
        $body = read_json_body();
        $fileInput = null;

        $possibleFileKeys = ['file', 'image', 'audio', 'video', 'document', 'attachment', 'media'];
        foreach ($possibleFileKeys as $key) {
            if (!empty($_FILES[$key]) && is_array($_FILES[$key]) && !empty($_FILES[$key]['tmp_name'])) {
                $fileInput = $_FILES[$key];
                break;
            }
        }

        $filePath = null;
        if ($fileInput) {
            $filePath = upload_image_file($fileInput, 'common')
                ?? upload_audio_file($fileInput, 'audio')
                ?? upload_video_file($fileInput, 'videos')
                ?? upload_document_file($fileInput, 'documents');
        }

        if (!$filePath) {
            foreach ($possibleFileKeys as $key) {
                if (!empty($body[$key]) && is_string($body[$key])) {
                    $filePath = process_image_input($body[$key], 'common');
                    if ($filePath) break;
                }
            }
        }

        if (empty($filePath)) {
            return ['status' => 'error', 'message' => trans('file_upload_required')];
        }

        $fullUrl = format_image_url($filePath);

        return [
            'status' => 'success',
            'message' => trans('file_uploaded_success') ?? 'File uploaded successfully',
            'data' => [
                'file_path' => $filePath,
                'file_url' => $fullUrl,
                'link' => $fullUrl
            ]
        ];
    }
}
