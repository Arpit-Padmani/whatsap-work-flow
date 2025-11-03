<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");



// Load your WhatsApp functions
require_once "whatsappSendMsg.php"; // contains sendWhatsAppMessage() and sendWhatsAppTextMessage()

// Read POST data
$input = json_decode(file_get_contents("php://input"), true);
writeLogg("*****************************************************************************************");
writeLogg("Incoming Request: " . json_encode($input));

if (!$input || !isset($input['number']) || !isset($input['message'])) {
    writeLogg("❌ Invalid request: missing fields");
    echo json_encode(["status" => "error", "message" => "Invalid request, required fields missing."]);
    exit;
}

// WhatsApp API credentials
$authToken = "EAAcusGlAK00BPtOT7SkMC4D8DZB48kdbbIZBgwKSQNaakwDwBs86A9QTMCKqVB39SD2qdlvCunJPsH7It1IfOZAmTBlfn5Qiil9JrThMSFg2Nbv8nsee4eXroCIe2pi5Pm3KJr42uZATcveCG2sQqEEEHWHbHeSxtnqgLlZAtIF2ZABGqpdUzwwwq45C4K";
$phone_number_id = "642760735595014";
$version = "v22.0";

// Parameters from request
$receiver_number = $input['number'];
$messageText  = isset($input['message']) ? $input['message'] : ".";
$parameters = [
    [
        "type" => "text",
        "parameter_name" => "msg",
        "text" => $messageText
    ]
];

try {

    writeLogg("*****************************************************************************************");
    writeLogg("📩 Sending template message to $receiver_number using template: $templateName");
    $res = sendWhatsAppMessage($authToken, $receiver_number, "ventas_greetings", $parameters, $version, $phone_number_id);
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

    echo json_encode(["status" => "success", "message" => "Template message sent successfully", "Response" => $resDecoded]);
    writeLogg("*****************************************************************************************");
} catch (Exception $e) {
    writeLogg("❌ Exception: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Exception occurred: " . $e->getMessage()]);
    exit;
}
