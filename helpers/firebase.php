<?php

function firebaseBase64UrlEncode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getFirebaseAccessToken($serviceAccountPath)
{
    if (!file_exists($serviceAccountPath)) {
        return [
            "status" => "error",
            "message" => "Firebase service account file not found"
        ];
    }

    $serviceAccount = json_decode(file_get_contents($serviceAccountPath));

    if (
        !$serviceAccount ||
        empty($serviceAccount->client_email) ||
        empty($serviceAccount->private_key) ||
        empty($serviceAccount->project_id)
    ) {
        return [
            "status" => "error",
            "message" => "Invalid Firebase service account file"
        ];
    }

    $jwtHeader = json_encode([
        "alg" => "RS256",
        "typ" => "JWT"
    ]);

    $now = time();

    $jwtPayload = json_encode([
        "iss"   => $serviceAccount->client_email,
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud"   => "https://oauth2.googleapis.com/token",
        "exp"   => $now + 3600,
        "iat"   => $now
    ]);

    $unsignedJwt = firebaseBase64UrlEncode($jwtHeader) . "." . firebaseBase64UrlEncode($jwtPayload);

    $signature = "";
    openssl_sign($unsignedJwt, $signature, $serviceAccount->private_key, "SHA256");

    $jwt = $unsignedJwt . "." . firebaseBase64UrlEncode($signature);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://oauth2.googleapis.com/token",
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query([
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion"  => $jwt
        ]),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            "status" => "error",
            "message" => "Curl error while getting Firebase access token",
            "error" => $curlError
        ];
    }

    $decoded = json_decode($response, true);

    if (empty($decoded["access_token"])) {
        return [
            "status" => "error",
            "message" => "Failed to get Firebase access token",
            "response" => $decoded
        ];
    }

    return [
        "status" => "success",
        "access_token" => $decoded["access_token"],
        "project_id" => $serviceAccount->project_id
    ];
}

function normalizeFirebaseData($data = [])
{
    if (!is_array($data) || empty($data)) {
        return [];
    }

    if (array_keys($data) === range(0, count($data) - 1)) {
        return [];
    }

    $normalized = [];

    foreach ($data as $key => $value) {
        if (is_array($value) || is_object($value)) {
            $normalized[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_bool($value)) {
            $normalized[$key] = $value ? "1" : "0";
        } elseif ($value === null) {
            $normalized[$key] = "";
        } else {
            $normalized[$key] = (string)$value;
        }
    }

    return $normalized;
}

function sendFirebaseNotification($accessToken, $projectId, $token, $title, $body, $data = [], $image = "", $link = "")
{
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    $message = [
        "token" => $token,
        "notification" => [
            "title" => $title,
            "body"  => $body
        ],
        "android" => [
            "priority" => "high",
            "notification" => [
                "sound" => "default"
            ]
        ],
        "apns" => [
            "payload" => [
                "aps" => [
                    "sound" => "default"
                ]
            ]
        ]
    ];

    if (!empty($link)) {
        // Lets a clicked background/OS push notification open (or focus) this
        // URL in the browser — the Firebase Web SDK's service worker handles
        // this automatically once fcm_options.link is present, no custom
        // notificationclick listener needed in firebase-messaging-sw.js.
        $message["webpush"] = [
            "fcm_options" => [
                "link" => $link
            ]
        ];
    }

    $normalizedData = normalizeFirebaseData($data);

    if (!empty($normalizedData)) {
        $message["data"] = $normalizedData;
    }

    if (!empty($image)) {
        $message["notification"]["image"] = $image;
    }

    $payload = json_encode([
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return [
            "status" => "error",
            "message" => "Curl error while sending notification",
            "error" => $curlError
        ];
    }

    $decoded = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            "status" => "error",
            "message" => "Firebase send failed",
            "http_code" => $httpCode,
            "response" => $decoded ?: $response,
            "payload" => json_decode($payload, true) // مفيد جدًا في الديبج
        ];
    }

    return [
        "status" => "success",
        "response" => $decoded
    ];
}