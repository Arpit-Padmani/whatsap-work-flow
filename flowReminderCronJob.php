<?php
include('helpers.php');
include('whatsappSendMsg.php');

// ✅ Copy config from webhook.php (do not include webhook.php!)
$accessToken = 'EAAR8YYnJJZAIBPIZBZBuYO2d0tzPgWtZA974tu0MmZBiHi4lT2wOYaZBYpYepWQl2A87pyZBLY6CPc5iCFXzeocphw31XmG71m3NOZA53Y0GtIW4eCA2DSGLKwkwrIG44UZBXuCvptqahYdihODjZApCFT2NHech2ZBA3r7kUVnIANikXZAYm6P0Prm4Ivvnm0LQegZDZD';
$version = "v22.0";
$phone_number_id = 713193288549152;
function writeLoggg($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}
$logFile = __DIR__ . '/cron_log.log';
$file = __DIR__ . "/session_data.json";   // ✅ same filename as webhook.php
$sessionData = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// ✅ Log cron start
writeLoggg("=======================================Cron Started====================================");
$remindersSent = 0;

function SendToCRM($sessionData, $phone)
{
    $url = "https://crm.lorencesurfaces.com/api/WhatsappAPIs/AddLead";
    $flowTitle   = isset($sessionData[$phone]['flowtitle']) ? $sessionData[$phone]['flowtitle'] : '';
    $userPhone   = isset($sessionData[$phone]['phonenumber']) ? $sessionData[$phone]['phonenumber'] : '';
    $userName    = isset($sessionData[$phone]['name']) ? $sessionData[$phone]['name'] : ''; {

        $remarks = "Remarks ";
        if (!empty($name)) {
            $remarks .= "Name: {$name}\n";
        }
        if (!empty($flowtitle)) {
            $remarks .= "Inquiry Type: {$flowtitle}\n";
        }
        if (!empty($phonenumber)) {
            $remarks .= "Phone: +{$phonenumber}\n";
        }
        if(!empty($phone)){
            $remarks .= "Phone: +{$phone}\n";
        }
        $data = [
            "menuName" => "New Lead Menu",
            "leadDetails" => [
                "companyname" => $sessionData[$phone]['name'] ?? 'Whatsapp',
                "email" =>  null,
                "contactno" => '+' . $sessionData[$phone]['phonenumber'] ?? '+' . $phone,
                "whatsappno" => '+' . $sessionData[$phone]['phonenumber'] ?? '+' . $phone,
                "website" => null,
                "country" =>  null,
                "state" => null,
                "city" => null,
                "address" => null,
                "managername" => $sessionData[$phone]['name'] ?? null,
                "manageremail" => null,
                "managercontactno" => null,
                "managerwhatsappno" => null,
                "instagramlink" => null,
                "facebooklink" => null,
                "linkedinlink" => null,
                "leadsource" => 'Chatbot - ' . $sessionData[$phone]['flowtitle'] ?? 'Chatbot Inquiry',
                "remarks" => $remarks,
                "arrivaldate" => null,
                "stageid" => 279,
                "tagid" => null,
                "agencyid" => 1,
                "agencyname" => null,
                "isclient" => false,
                "isrejected" => false,
                "createdby" => null,
                "createddate" => null,
                "modifiedby" => null,
                "modifieddate" => null,
                "deletedat" => null,
                "totalRecords" => 1,
                "userid" => null,
                "username" => null,
                "notificationMessage" => null,
                "googleMapLink" => null,
                "isGenerateLead" => null,
                "remarkUpdateDate" => null
            ]
        ];
        if (!empty(trim($remarks))) {
            writeLoggg('------------------------------------------------------------------------------------------------');
            writeLoggg("Posting to Ventas API with payload: " . json_encode($data));
            writeLoggg('------------------------------------------------------------------------------------------------');

            $payload = json_encode($data);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                $error = curl_error($ch);
                writeLoggg("❌ cURL error while posting to Ventas: $error");
            } else {
                writeLoggg("✅ Posted to Ventas (HTTP $httpCode): $response");
            }

            curl_close($ch);

            return [
                "status"   => $response === false ? "error" : "success",
                "httpCode" => $httpCode,
                "response" => $response ?? null
            ];
        } else {
            writeLoggg("⚠️ No valid remarks available. Skipping Ventas API call.");
            return ["status" => "skipped", "httpCode" => null, "response" => null];
        }
    }
}

foreach ($sessionData as $phone => $data) {
    if (isset($data['last_activity'])) {
        $inactiveTime = time() - $data['last_activity'];
        if ($inactiveTime > 60) { // 1 hour = 3600 sec
            $reminderCount = $sessionData[$phone]['reminder_count'] ?? 0;
             $reminderMessages = [
                0 => "👋 Still there? We’d love to assist you!",
                1 => "⏳ Don’t leave your query halfway—let’s wrap this up!",
                2 => "✅ Just one reply away from getting the right solution!"
            ];
            
            if ($reminderCount < 3) {
                $reminderMsg = $reminderMessages[$reminderCount];
                // ✅ Send generic reminder
                $response = sendWhatsAppTextMessage(
                    $accessToken,
                    $phone,
                    [
                        "messaging_product" => "whatsapp",
                        "to" => $phone,  // <-- CORRECT: use $userId, not $phone_number
                        "type" => "text",
                        "text" => ["body" => $reminderMsg]
                    ],
                    $version,
                    $phone_number_id
                );

                writeLoggg("Inactivity reminder sent to user: $phone");
                writeLoggg("WhatsApp API Response: " . json_encode($response));

                // ✅ Update last_activity so reminder is not sent repeatedly
                $sessionData[$phone]['reminder_count'] = $reminderCount + 1;
                $sessionData[$phone]['last_activity'] = time();
            } elseif ($reminderCount === 3) {
                // ✅ Final timeout message
                $response = sendWhatsAppTextMessage(
                    $accessToken,
                    $phone,
                    [
                        "messaging_product" => "whatsapp",
                        "to" => $phone,
                        "type" => "text",
                        "text" => ["body" => "⚠️ Your session has expired due to inactivity. Please start again."]
                    ],
                    $version,
                    $phone_number_id
                );
                SendToCRM($sessionData, $phone);

                writeLoggg("Final timeout message sent to user: $phone");
                writeLoggg("WhatsApp API Response: " . json_encode($response));

                // Option 1: Reset session (clear user data)
                unset($sessionData[$phone]);
            }

            // Option 2 (if you don’t want to delete): just mark as expired
            // $sessionData[$phone]['expired'] = true;
        }
    }
}
// foreach ($sessionData as $userId => $session) {
//     if (isset($session['pending'])) {
//         $pending = $session['pending'];
//         if ($pending['status'] === 'pending' && (time() - $pending['sent_at']) >= 180) {

//             // ✅ Send reminder (resend question)
//             $response = sendWhatsAppTextMessage(
//                 $accessToken,
//                 $userId,   // <-- CORRECT: $userId is the phone number
//                 [
//                     "messaging_product" => "whatsapp",
//                     "to" => $userId,  // <-- CORRECT: use $userId, not $phone_number
//                     "type" => "text",
//                     "text" => ["body" => "⏰ Reminder: " . $pending['question']]
//                 ],
//                 $version,
//                 $phone_number_id
//             );

//             // ✅ Log reminder details
//             writeLoggg("Reminder sent to user: $userId | Question: " . $pending['question']);
//             writeLoggg("WhatsApp API Response: " . json_encode($response));

//             // ✅ update status
//             $sessionData[$userId]['pending']['status'] = 'reminded';
//             $remindersSent++;
//         }
//     }
// }

file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));

// ✅ Log cron end
if ($remindersSent > 0) {
    writeLoggg("Total reminders sent in this run: $remindersSent");
} else {
    writeLoggg("No pending reminders found in this run.");
}
writeLoggg("=======================================Cron Finished====================================");
