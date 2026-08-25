<?php

if (!function_exists('easykash_config')) {
    function easykash_config(): array {
        static $config = null;
        if ($config === null) {
            $config = require __DIR__ . '/../config/easykashConfig.php';
        }
        return $config;
    }
}

if (!function_exists('easykash_create_payment')) {
    function easykash_create_payment(array $params): array {
        $config = easykash_config();

        $payload = [
            'amount' => (float)$params['amount'],
            'currency' => $params['currency'] ?? $config['currency'],
            'name' => $params['name'],
            'email' => $params['email'],
            'mobile' => $params['mobile'],
            'redirectUrl' => $params['redirectUrl'],
            'customerReference' => (int)$params['customerReference'],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $config['pay_url'],
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'authorization: ' . $config['api_key'],
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['status' => 'error', 'message' => 'Curl error while contacting EasyKash', 'error' => $curlError];
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300 || empty($decoded['redirectUrl'])) {
            return ['status' => 'error', 'message' => 'EasyKash rejected the payment request', 'http_code' => $httpCode, 'response' => $decoded ?: $response];
        }

        return ['status' => 'success', 'redirectUrl' => $decoded['redirectUrl']];
    }
}


/**
 * EasyKash's own docs/examples are inconsistent about field casing (ProductCode vs
 * productCode, etc). Look up a callback field regardless of how it was actually cased.
 */
if (!function_exists('easykash_ci_get')) {
    function easykash_ci_get(array $data, string $key): string {
        foreach ($data as $k => $v) {
            if (strcasecmp((string)$k, $key) === 0) {
                return (string)$v;
            }
        }
        return '';
    }
}

if (!function_exists('easykash_log')) {
    function easykash_log(string $message): void {
        $dir = __DIR__ . '/../logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/easykash_callback.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND
        );
    }
}

if (!function_exists('easykash_verify_callback_signature')) {
    function easykash_verify_callback_signature(array $data): bool {
        $config = easykash_config();

        $signatureHash = easykash_ci_get($data, 'signatureHash');
        if (empty($signatureHash)) {
            return false;
        }

        $concatenated =
            easykash_ci_get($data, 'ProductCode') .
            easykash_ci_get($data, 'Amount') .
            easykash_ci_get($data, 'ProductType') .
            easykash_ci_get($data, 'PaymentMethod') .
            easykash_ci_get($data, 'status') .
            easykash_ci_get($data, 'easykashRef') .
            easykash_ci_get($data, 'customerReference');

        $computed = hash_hmac('sha512', $concatenated, $config['hmac_secret']);
        $isValid = hash_equals($computed, (string)$signatureHash);

        if (!$isValid) {
            easykash_log('Signature mismatch. Concatenated="' . $concatenated . '" computed=' . $computed . ' received=' . $signatureHash);
        }

        return $isValid;
    }
}
