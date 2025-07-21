<?php
include ('whatsappSendMsg.php');


$hubVerifyToken = 'lorence_surfaces_workflow';
$accessToken = 'EAAR8YYnJJZAIBPOUuruCCZCKp6jzZCmh4v6SxcJPAN7zOzriWSsRYWvdZByOxkwm1J0xe2l6MksZCFKQxBotKyyvLrfMHMghdphjvovFdZApekiqUVUjAZAYPP0iU7zfZBx8fDxnxInYyKb68u2J4nFOtf9NfVX0LIxQMEEdGv0PiuXx2xr9M1JudjvX8lJ8A9yAGvAFvXWOovIeZCLkoCsWlnJZADi8WmntNT6ZBMT4UUWP2AZD';

$logFile = __DIR__ . '/webhook_session.log';
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_challenge']) && isset($_GET['hub_verify_token']) && $_GET['hub_verify_token'] === $hubVerifyToken) {
    writeLog("Verification successful. hub_challenge returned.");
    echo $_GET['hub_challenge'];
    exit;
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);
file_put_contents("response.json", $raw_input);

// Parse incoming message
$phone_number = $data['entry'][0]['changes'][0]['value']['messages'][0]['from'];
$message = $data['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];

writeLog($message);
$version = "v22.0";
$phone_number_id = 642760735595014;

$inqueryTemplate = [
        "messaging_product" => "whatsapp",
        "to" => "917096305498",
        "type" => "interactive",
        "interactive" => [
            "type" => "list",
            "header" => [
                "type" => "text",
                "text" => "Header"
            ],
            "body" => [
                "text" => "Hi Himanshu ðŸ‘‹ How may I assist you today ?"
            ],
            "footer" => [
                "text" => ""
            ],
            "action" => [
                "button" => "Choose Inquiry",
                "sections" => [
                    [
                        "title" => "New Section",
                        "rows" => [
                            [
                                "id" => "product_inquiry",
                                "title" => "Product / Tiles Inquiry",
                                "description" => "Ask about tile designs, specifications, or availability."
                            ],
                            [
                                "id" => "dealership_inquiry",
                                "title" => "Dealership Inquiry",
                                "description" => "Interested in becoming a dealer or distributor ?"
                            ],
                            [
                                "id" => "exportImport_inqiry",
                                "title" => "Export / Import Inquiry",
                                "description" => "For queries related to international trade or logistics."
                            ],
                            [
                                "id" => "request_call_back",
                                "title" => "Request a Call Back",
                                "description" => "Let us know and weâ€™ll get in touch shortly."
                            ]
                        ],
                    ]
                ]
            ]
        ]
];


$tilesSelectionTemplate = [
        "messaging_product" => "whatsapp",
        "to" => "917096305498",
        "type" => "interactive",
        "interactive" => [
            "type" => "list",
            "header" => [
                "type" => "text",
                "text" => "Tile Selection"
            ],
            "body" => [
                "text" => "Thank you! Please choose how you'd like to search tiles"
            ],
            "footer" => [
                "text" => ""
            ],
            "action" => [
                "button" => "Choose tiles",
                "sections" => [
                    [
                        "title" => "Search Options",
                        "rows" => [
                            [
                                "id" => "search_by_area",
                                "title" => "By Area",
                                "description" => "Find tiles based on where they’ll be used — kitchen, bathroom, outdoor, etc."
                            ],
                            [
                                "id" => "search_by_size",
                                "title" => "By Size",
                                "description" => "Browse tile collections categorized by size (e.g. 600x600mm, 800x1600mm)."
                            ],
                            [
                                "id" => "search_by_surface",
                                "title" => "By Surface",
                                "description" => "Explore tiles by surface finish like glossy, matte, sugar, etc."
                            ],
                            [
                                "id" => "search_by_look",
                                "title" => "By Look & Feel",
                                "description" => "Choose tiles based on style, texture, and overall aesthetic."
                            ]
                        ],
                    ]
                ]
            ]
        ]
];


$entry = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;

$file = 'session_data.json';

if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}

$sessionData = json_decode(file_get_contents($file), true);


if (isset($entry['text']['body'])) {
    if (!isset($sessionData[$phone_number])) {
        writeLog("New user detected. Sending inquiry list.");
        sendWhatsAppTextMessage($accessToken, $phone_number, $inqueryTemplate, $version, $phone_number_id);
        
        $sessionData[$phone_number] = ['stage' => 1];
        file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
    } else {
        writeLog("User already exists in session. Skipping inquiry list.");
    }
}



// Handle list reply
if (isset($entry['interactive']['list_reply'])) {
    $reply = $entry['interactive']['list_reply'];
    $reply_id = $reply['id'] ?? '';
    $reply_title = $reply['title'] ?? '';
    $reply_description = $reply['description'] ?? '';



    $reply_type = $reply_id; 

    if (!isset($sessionData[$reply_type])) {
        $sessionData[$reply_type] = ['stage' => 1];
        writeLog("First entry in session file");
    }


    switch ($reply_id) {
        
        case "product_inquiry":
            if (!isset($sessionData[$reply_type])) {
                $sessionData[$reply_type] = ['stage' => 1];
            }
            switch($sessionData[$reply_type]['stage']){
                case 1:
                    writeLog("Double switch case");
                    sendWhatsAppMessage($accessToken, "917096305498", "ask_pincode", $version, $phone_number_id);
                    writeLog("Message senddned successfully");

                    $message = strtolower(trim($entry['text']['body']));
                    writeLog("message getted after pincode",$message);

                    $sessionData[$user_id]['stage'] = 2;
                    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                    echo "step 1";
                    break;

                case 2:
                    if (isset($entry['text']['body'])) {
                    $pincode = trim($entry['text']['body']);
                    $sessionData[$phone_number]['pincode'] = $pincode;
                    writeLog("User entered pincode: $pincode");

                    if (strlen($pincode) == 6 && ctype_digit($pincode)) {
                        $location = getCityStateFromPincode($pincode);

                        if ($location) {
                            writeLog("City: " . $location['city'] . ", State: " . $location['state']);

                            $sessionData[$phone_number]['pincode'] = $pincode;
                            $sessionData[$phone_number]['city'] = $location['city'];
                            $sessionData[$phone_number]['state'] = $location['state'];

                            sendWhatsAppTextMessage($accessToken, $phone_number, $tilesSelectionTemplate, $version, $phone_number_id);
                            $sessionData[$phone_number]['stage'] = 3;
                        } else {
                            sendWhatsAppTextMessage($accessToken, $phone_number, [
                                "messaging_product" => "whatsapp",
                                "to" => $phone_number,
                                "type" => "text",
                                "text" => [
                                    "body" => "❌ Invalid pincode. Please try again with a valid 6-digit pincode."
                                ]
                            ], $version, $phone_number_id);
                             $sessionData[$phone_number]['stage'] = 2;
                        }

                        file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                    }
                }
                break;

            }
    }




}

?>
