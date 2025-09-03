<?php
include('helpers.php');
include('whatsappSendMsg.php');

// ✅ Copy config from webhook.php (do not include webhook.php!)
$accessToken = 'EAAR8YYnJJZAIBPIZBZBuYO2d0tzPgWtZA974tu0MmZBiHi4lT2wOYaZBYpYepWQl2A87pyZBLY6CPc5iCFXzeocphw31XmG71m3NOZA53Y0GtIW4eCA2DSGLKwkwrIG44UZBXuCvptqahYdihODjZApCFT2NHech2ZBA3r7kUVnIANikXZAYm6P0Prm4Ivvnm0LQegZDZD';
$version = "v22.0";
$phone_number_id = 713193288549152;

$file = __DIR__ . "/session_data.json";   // ✅ same filename as webhook.php
$sessionData = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// ✅ Log cron start
writeLog("=======================================Cron Started====================================");
$remindersSent = 0;

foreach ($sessionData as $userId => $session) {
    if (isset($session['pending'])) {
        $pending = $session['pending'];
        if ($pending['status'] === 'pending' && (time() - $pending['sent_at']) >= 180) {

            // ✅ Send reminder (resend question)
            $response = sendWhatsAppTextMessage(
                $accessToken,
                $userId,   // <-- CORRECT: $userId is the phone number
                [
                    "messaging_product" => "whatsapp",
                    "to" => $userId,  // <-- CORRECT: use $userId, not $phone_number
                    "type" => "text",
                    "text" => ["body" => "⏰ Reminder: " . $pending['question']]
                ],
                $version,
                $phone_number_id
            );

            // ✅ Log reminder details
            writeLog("Reminder sent to user: $userId | Question: " . $pending['question']);
            writeLog("WhatsApp API Response: " . json_encode($response));

            // ✅ update status
            $sessionData[$userId]['pending']['status'] = 'reminded';
            $remindersSent++;
        }
    }
}

file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));

// ✅ Log cron end
if ($remindersSent > 0) {
    writeLog("Total reminders sent in this run: $remindersSent");
} else {
    writeLog("No pending reminders found in this run.");
}
writeLog("=======================================Cron Finished====================================");
