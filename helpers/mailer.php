<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('sendMailMessage')) {
    function sendMailMessage($to, $subject, $htmlBody, $plainBody = "", $options = [])
    {
        require_once __DIR__ . "/../vendor/autoload.php";

        $mailConfig = require __DIR__ . "/../config/mailConfig.php";

        try {
            if (empty($to)) {
                return [
                    "status" => "error",
                    "message" => "Recipient email is required"
                ];
            }

            if (empty($subject)) {
                return [
                    "status" => "error",
                    "message" => "Email subject is required"
                ];
            }

            if (empty($htmlBody)) {
                return [
                    "status" => "error",
                    "message" => "Email body is required"
                ];
            }

            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = $mailConfig["host"] ?? "host03.bigbangservers.com";
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig["username"] ?? "najeh@ahmed.its.ae";
            $mail->Password = $mailConfig["password"] ?? "najeh@ahmed.its.ae";
            $mail->Port = $mailConfig["port"] ?? 587;

            if (($mailConfig["encryption"] ?? "tls") === "ssl") {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->CharSet = "UTF-8";
            $mail->Encoding = "base64";

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $fromEmail = $options["from_email"] ?? $mailConfig["from_email"];
            $fromName  = $options["from_name"] ?? $mailConfig["from_name"];

            $mail->setFrom($fromEmail, $fromName);

            if (is_array($to)) {
                foreach ($to as $email) {
                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addAddress($email);
                    }
                }
            } else {
                if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    return [
                        "status" => "error",
                        "message" => "Invalid recipient email"
                    ];
                }

                $mail->addAddress($to);
            }

            if (!empty($options["reply_to"]) && filter_var($options["reply_to"], FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo(
                    $options["reply_to"],
                    $options["reply_to_name"] ?? ""
                );
            }

            if (!empty($options["cc"]) && is_array($options["cc"])) {
                foreach ($options["cc"] as $ccEmail) {
                    if (!empty($ccEmail) && filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                        $mail->addCC($ccEmail);
                    }
                }
            }

            if (!empty($options["bcc"]) && is_array($options["bcc"])) {
                foreach ($options["bcc"] as $bccEmail) {
                    if (!empty($bccEmail) && filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
                        $mail->addBCC($bccEmail);
                    }
                }
            }

            if (!empty($options["attachments"]) && is_array($options["attachments"])) {
                foreach ($options["attachments"] as $attachment) {
                    if (is_string($attachment) && file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    }

                    if (
                        is_array($attachment) &&
                        !empty($attachment["path"]) &&
                        file_exists($attachment["path"])
                    ) {
                        $mail->addAttachment(
                            $attachment["path"],
                            $attachment["name"] ?? ""
                        );
                    }
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $plainBody ?: strip_tags($htmlBody);

            $mail->send();

            return [
                "status" => "success",
                "message" => "Email sent successfully"
            ];

        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => "Email could not be sent",
                "error" => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            return [
                "status" => "error",
                "message" => "Unexpected mail error",
                "error" => $e->getMessage()
            ];
        }
    }
}

if (!function_exists('send_smtp_email')) {
    function send_smtp_email(string $toEmail, string $subject, string $htmlBody): bool {
        $res = sendMailMessage($toEmail, $subject, $htmlBody);
        return ($res['status'] ?? '') === 'success';
    }
}
