<?php
$logFile = __DIR__ . '/whatsapp.log';

// === LOGGING FUNCTION ===
function writeLogg($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function sendWhatsAppMessage($authToken, $receiver_number, $templateName,  $version, $phone_number_id)
{
    $url = "https://graph.facebook.com/v22.0/642760735595014/messages?access_token=$authToken";
    $data = [
        "messaging_product" => "whatsapp",
        "to" => "917096305498",
        "type" => "template",
        "template" => [
            "name" => $templateName,
            "language" => [
                "code" => "en"
            ]
        ]
    ];

    $headers = [
        "Authorization: $authToken",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        writeLogg('Curl error (template): ' . curl_error($ch));
    }

    writeLogg("sendWhatsAppMessage response: " . $response);
    curl_close($ch);
}

function sendWhatsAppTextMessage($authToken, $receiver_number, $tempate, $version, $phone_number_id)
{
    $url = "https://graph.facebook.com/v22.0/597358536789220/messages?access_token=$authToken";

    writeLogg("Sending WhatsApp message to $receiver_number");
    writeLogg("Request URL: $url");
    writeLogg("Request Payload: " . json_encode($tempate));

    $headers = [
        "Authorization: $authToken",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($tempate));

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}


// function sendWhatsAppTextMessage($authToken, $receiver_number, $message, $version, $phone_number_id)
// {
//     $url = "https://graph.facebook.com/v22.0/597358536789220/messages?access_token=$authToken";

//     $data = [
//         "messaging_product" => "whatsapp",
//         "recipient_type" => "individual",
//         "to" => "917096305498",
//         "type" => "text",
//         "text" => [
//             "preview_url" => false,
//             "body" => $message
//         ]
//     ];

//     $headers = [
//         "Authorization: $authToken",
//         "Content-Type: application/json"
//     ];

//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $url);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

//     $response = curl_exec($ch);
//     curl_close($ch);

//     return $response;
// }
