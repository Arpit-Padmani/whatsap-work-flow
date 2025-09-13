<?php
function writeLog($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}
include('whatsappSendMsg.php');
$logFile = __DIR__ . '/cron_log.log';
// ✅ Config
$accessToken = 'EAAR8YYnJJZAIBPIZBZBuYO2d0tzPgWtZA974tu0MmZBiHi4lT2wOYaZBYpYepWQl2A87pyZBLY6CPc5iCFXzeocphw31XmG71m3NOZA53Y0GtIW4eCA2DSGLKwkwrIG44UZBXuCvptqahYdihODjZApCFT2NHech2ZBA3r7kUVnIANikXZAYm6P0Prm4Ivvnm0LQegZDZD';
$version = "v22.0";
$phone_number_id = 713193288549152;

$file = __DIR__ . "/session.json";
$sessionData = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// ✅ Log cron start
writeLog("=======================================Cron Started====================================");
$remindersSent = 0;

foreach ($sessionData as $userId => $messages) {
    foreach ($messages as $index => $msg) {
        if (isset($msg['isReplied']) && $msg['isReplied'] === false) {
            // Check if enough time has passed (e.g., 3 minutes = 180 seconds)
            $sentTime = strtotime($msg['timestamp']);
            if ((time() - $sentTime) >= 180) {
                 $payload = [
                    "messaging_product" => "whatsapp",
                    "to" => $userId,
                    "type" => "interactive",
                    "interactive" => [
                        "type" => "button",
                        "body" => [
                            "text" => "⏰ Reminder: " . $msg['template']
                        ],
                        "action" => [
                            "buttons" => $msg['buttons'] ?? [] // fallback if buttons not set
                        ]
                    ]
                ];

                // ✅ Send reminder message
                $response = sendWhatsAppTextMessage(
                    $accessToken,
                    $userId,
                    $payload,
                    $version,
                    $phone_number_id
                );

                // ✅ Log reminder details
                writeLog("Reminder sent to user: $userId | Template: " . $msg['template']);
                writeLog("WhatsApp API Response: " . json_encode($response));

                // ✅ Update status to avoid duplicate reminders
                $sessionData[$userId][$index]['isReplied'] = true;
                $remindersSent++;
            }
        }
    }
}

file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));

// ✅ Log cron end
if ($remindersSent > 0) {
    writeLog("Total reminders sent in this run: $remindersSent");
} else {
    writeLog("No reminders needed in this run.");
}
writeLog("=======================================Cron Finished====================================");