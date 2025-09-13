<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");



// Load your WhatsApp functions
require_once "whatsappSendMsg.php"; // contains sendWhatsAppMessage() and sendWhatsAppTextMessage()

// Read POST data
$input = json_decode(file_get_contents("php://input"), true);

writeLogg("Incoming Request: " . json_encode($input));

if (!$input || !isset($input['number']) || !isset($input['message_type'])) {
    writeLogg("❌ Invalid request: missing fields");
    echo json_encode(["status" => "error", "message" => "Invalid request, required fields missing."]);
    exit;
}

// WhatsApp API credentials
$authToken = "EAAcusGlAK00BPRrQ7Da74dZCsQZAZCznKrGomAZB7xmsfyOXSZAksxmtkfX3CeqAdgR8VzYWPewt3CHfElXm0PigkaWLoOd1Fgj6GFZArW4AacazhGH7b8nLT7OTo2STp4hfZCFPITOquGPedwZCZCOSm2h0G8f3opRFixIsLkBDNvSxs7HWq9Gbj8v4C493wRqp4QCfGrGsfy6OvsKRY2SDtcfkVMOgGngTbX0nvrgChMSkZD"; 
$phone_number_id = "642760735595014"; 
$version = "v22.0";

$sessionFile = __DIR__ . "/session.json";
// Parameters from request
$receiver_number = $input['number'];
$message_type = $input['message_type']; // "text" or "template"

// Optional params
$templateName = isset($input['template']) ? $input['template'] : null;
$messageText  = isset($input['text']) ? $input['text'] : null;

try {
    if ($message_type === "template") {
        if (!$templateName) {
            writeLogg("❌ Template message requested but no template name given");
            echo json_encode(["status" => "error", "message" => "Template name required."]);
            exit;
        }

        writeLogg("📩 Sending template message to $receiver_number using template: $templateName");
        $res = sendWhatsAppMessage($authToken, $receiver_number, $templateName, $version, $phone_number_id);
        writeLogg("✅ Template Response: " . $res);
        
        $resDecoded = json_decode($res, true);
        
        if (isset($resDecoded['error'])) {
            // WhatsApp returned error
                echo json_encode([
                    "status" => "error",
                    "message" => $resDecoded['error']['message'] ?? "Unknown error",
                    "response" => $resDecoded
                ]);
            exit;
        }

        $sessionData = [];
        if (file_exists($sessionFile)) {
            $jsonContent = file_get_contents($sessionFile);
            $sessionData = json_decode($jsonContent, true) ?? [];
        }

        // update with receiver_number
        $sessionData[$receiver_number][] = [
            "template"   => $templateName,
            "isReplied"  => false,
            "timestamp"  => date("Y-m-d H:i:s")
        ];

        // write back
        file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
  writeLogg("-------------------------------------------------------------------------------------------" );
        writeLogg("template updated successfully " );
        writeLogg("-------------------------------------------------------------------------------------------" );




        echo json_encode(["status" => "success", "message" => "Template message sent successfully", "response" => $res]);

    } elseif ($message_type === "text") {
        
        if (!$messageText) {
            writeLogg("❌ Text message requested but no text provided");
            echo json_encode(["status" => "error", "message" => "Text message required."]);
            exit;
        }

        $payload = [
            "messaging_product" => "whatsapp",
            "to" => $receiver_number,
            "type" => "text",
            "text" => [
                "body" => $messageText
            ]
        ];

        writeLogg("📩 Sending text message to $receiver_number: " . $messageText);
        $res = sendWhatsAppTextMessage($authToken, $receiver_number, $payload, $version, $phone_number_id);
        writeLogg("✅ Text Response: " . $res);
        $resDecoded = json_decode($res, true);
        
        if (isset($resDecoded['error'])) {
            // WhatsApp returned error
                echo json_encode([
                    "status" => "error",
                    "message" => $resDecoded['error']['message'] ?? "Unknown error",
                    "response" => $resDecoded
                ]);
            exit;
        }
        echo json_encode(["status" => "success", "message" => "Text message sent successfully", "response" => $res]);

    } 
    elseif ($message_type === "button") {
    if (!$messageText || !isset($input['buttons']) || !is_array($input['buttons'])) {
        writeLogg("❌ Button message requested but missing text or buttons");
        echo json_encode(["status" => "error", "message" => "Button text and at least one button required."]);
        exit;
    }

    // Check max 3 buttons
    if (count($input['buttons']) > 3) {
        writeLogg("❌ More than 3 buttons provided");
        echo json_encode(["status" => "error", "message" => "WhatsApp allows a maximum of 3 buttons."]);
        exit;
    }

    // Build button array
    $buttonsArray = [];
    foreach ($input['buttons'] as $btn) {
        if (isset($btn['id']) && isset($btn['title'])) {
            $buttonsArray[] = [
                "type" => "reply",
                "reply" => [
                    "id" => $btn['id'],
                    "title" => $btn['title']
                ]
            ];
        }
    }

    if (count($buttonsArray) === 0) {
        echo json_encode(["status" => "error", "message" => "No valid buttons found"]);
        exit;
    }

    // Final payload
    $payload = [
        "messaging_product" => "whatsapp",
        "to" => $receiver_number,
        "type" => "interactive",
        "interactive" => [
            "type" => "button",
            "body" => [
                "text" => $messageText
            ],
            "action" => [
                "buttons" => $buttonsArray
            ]
        ]
    ];

    writeLogg("📩 Sending button message to $receiver_number: " . json_encode($payload));
    $res = sendWhatsAppTextMessage($authToken, $receiver_number, $payload, $version, $phone_number_id);
    writeLogg("✅ Button Response: " . $res);
    
     $resDecoded = json_decode($res, true);
        
        if (isset($resDecoded['error'])) {
            // WhatsApp returned error
                echo json_encode([
                    "status" => "error",
                    "message" => $resDecoded['error']['message'] ?? "Unknown error",
                    "response" => $resDecoded
                ]);
            exit;
        }
        
        $sessionData = [];
        if (file_exists($sessionFile)) {
            $jsonContent = file_get_contents($sessionFile);
            $sessionData = json_decode($jsonContent, true) ?? [];
        }

        // update with receiver_number
        $sessionData[$receiver_number][] = [
            "template"   => $payload,
            "isReplied"  => false,
            "timestamp"  => date("Y-m-d H:i:s")
        ];

        // write back
        file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
        writeLogg("-------------------------------------------------------------------------------------------" );
        writeLogg("template updated successfully " );
        writeLogg("-------------------------------------------------------------------------------------------" );


    echo json_encode(["status" => "success", "message" => "Button message sent successfully", "response" => $res]);
    }else {
        writeLogg("❌ Invalid message_type: " . $message_type);
        echo json_encode(["status" => "error", "message" => "Invalid message type"]);
    }
} catch (Exception $e) {
    writeLogg("⚠️ Exception: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}