<?php
$logFile = __DIR__ . '/webhook_wh.log';

// === LOGGING FUNCTION ===
function writeLogg($message) {
     global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

function sendWhatsAppMessage($authToken,$receiver_number, $templateName,$parameters,  $version, $phone_number_id)
{
    writeLogg("Template Name: " . trim($templateName));
    writeLogg("Parameters: " . json_encode($parameters));
    $url = "https://graph.facebook.com/v22.0/823388174187369/messages?access_token=$authToken";
    $data = [
        "messaging_product" => "whatsapp",
        "to" => $receiver_number,
        "type" => "template",
        "template" => [
            "name" => trim($templateName),
            "language" => ["code" => "en"], // must match approved template language
            "components" => [
                [
                    "type" => "body",
                    "parameters" => $parameters // array of objects: [{"type":"text","text":"..."}]
                ]
            ]
        ]
    ];
    writeLogg("********************************************************************************************************************");
    writeLogg("Payload: " . json_encode($data, JSON_UNESCAPED_UNICODE));


    $headers = [
        "Authorization: Bearer $authToken",
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
    $url = "https://graph.facebook.com/v22.0/823388174187369/messages?access_token=$authToken";

    writeLogg("Sending WhatsApp message to $receiver_number");
    writeLogg("Request URL: $url");
    writeLogg("Request Payload: " . json_encode($tempate));

    $headers = [
        "Authorization: Bearer $authToken",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($tempate));

    $response = curl_exec($ch);
    writeLogg("Request Payload: " . json_encode($response));
    curl_close($ch);

    return $response;
}

?>