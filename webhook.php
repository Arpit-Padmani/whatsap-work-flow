<?php
include('whatsappSendMsg.php');


$hubVerifyToken = 'lorence_surfaces_workflow';
$accessToken = 'EAAR8YYnJJZAIBPAZC6QTJP9mPCP6bb0ZAPatDGEqthPyLfsITwYqnYWuRHrB0D4ZACcLAZCm5WaseqgZALWLO50c6mZCTx85dnZBBXt2IjxFBV1GUTBaP0hjiHmonhEfVSbPIB4wJZBj7fb2zZBPOea8JMVA3LfrxEcltgWfZBSTdUxykZBDrMhr1wVZAf53ZCo2x3wPy7b30E5XF92I12tqJ7p69cbwALY8LeU3f4hLuRX36KIwZDZD';

$logFile = __DIR__ . '/webhook_session.log';
function writeLog($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}
function getCityStateFromPincode($pincode)
{
    $url = "https://cqpplefitting.com/pincode/Api/findStateAndCityByPincode.php";

    $payload = json_encode([
        "pincode" => $pincode
    ]);

    $headers = [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if ($data['status'] === "true") {
            return [
                'state' => ucfirst($data['state']),
                'city' => ucfirst($data['city'])
            ];
        }
    }

    return null;
}
function handleMaxAttempts(&$sessionData, $phone_number, $maxAttempts, $failureTemplate, $retryTemplate, $stage, $accessToken, $version, $phone_number_id)
{
    if (!isset($sessionData[$phone_number]['invalid_attempts'])) {
        $sessionData[$phone_number]['invalid_attempts'] = 0;
    }

    $sessionData[$phone_number]['invalid_attempts']++;

    if ($sessionData[$phone_number]['invalid_attempts'] >= $maxAttempts) {
        sendWhatsAppTextMessage($accessToken, $phone_number, $failureTemplate, $version, $phone_number_id);
        unset($sessionData[$phone_number]); // Clear session on failure
    } else {
        sendWhatsAppTextMessage($accessToken, $phone_number, $retryTemplate, $version, $phone_number_id);
        $sessionData[$phone_number]['stage'] = $stage; // Retry same stage
    }
}
function completeAndClearSession($accessToken, $phone_number, $sessionData, $version, $phone_number_id, $file)
{
    // Format the collected data
    $userData = $sessionData[$phone_number];
    writeLog($userData);
    $summary = "✅ Thank you for your response! Here’s what we received:\n\n";
    $summary .= "🧾 Company Name: " . '*' . ($userData['companyname'] . '*' ?? '-') . "\n";
    $summary .= "🌍 Country: " . '*' . ($userData['countryname'] ?? '-') . '*'  . "\n";
    $summary .= "✉️ Email: " . '*' . ($userData['email'] ?? '-') . '*'  . "\n";
    $summary .= "🏷️ Brand Name: " . '*' . ($userData['brandname'] . '*' ?? '-') . "\n";
    $summary .= "\nWe’ll get in touch with you shortly.";

    // Create message template
    $thankYouTemplate = [
        "messaging_product" => "whatsapp",
        "to" => $phone_number,
        "type" => "text",
        "text" => [
            "body" => $summary
        ]
    ];

    // Send the summary to user
    // sendWhatsAppTextMessage($accessToken, $phone_number, $thankYouTemplate, $version, $phone_number_id);

    // Log and clear session
    writeLog("Session completed for $phone_number. Clearing session data.");
    unset($sessionData[$phone_number]);

    // Save updated (cleared) data
    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
}
function postDataToVentas($accessToken, $phone_number, $sessionData, $version, $phone_number_id, $file)
{
    $url = "http://192.168.1.28:5003/api/WhatsappAPIs/AddLead";

    $data = []; // default empty

    if ($sessionData[$phone_number]['flow'] === 'product_inquiry') {
        $remarks = "
    Name: {$sessionData[$phone_number]['username']} , \n
    Inquiry Type: {$sessionData[$phone_number]['flowtitle']} , \n
    Pincode: {$sessionData[$phone_number]['pincode']} , \n
    Search Preference: {$sessionData[$phone_number]['tiles_title']} , \n
    {$sessionData[$phone_number]['tiles_title']} : {$sessionData[$phone_number]['tile_type']} , \n
    Required Area: {$sessionData[$phone_number]['squre_feet']}

";

        writeLog('------------------------------------------------------------------------------------------------');
        writeLog($remarks);
        writeLog('------------------------------------------------------------------------------------------------');
        $data = [
            "menuName" => "New Lead Menu",
            "leadDetails" => [
                "companyname" => $sessionData[$phone_number]['username'] ?? 'Whatsapp',
                "email" =>  null,
                "contactno" => $sessionData[$phone_number]['phonenumber'] ?? $phone_number,
                "whatsappno" => $sessionData[$phone_number]['phonenumber'] ?? $phone_number,
                "website" => null,
                "country" =>  null,
                "state" => $sessionData[$phone_number]['state'] ?? '',
                "city" => $sessionData[$phone_number]['city'] ?? '',
                "address" => null,
                "managername" => $sessionData[$phone_number]['username'] ?? null,
                "manageremail" => null,
                "managercontactno" => null,
                "managerwhatsappno" => null,
                "instagramlink" => null,
                "facebooklink" => null,
                "linkedinlink" => null,
                "leadsource" => 'Chatbot - ' . $sessionData[$phone_number]['flowtitle'] ?? 'Chatbot Inquiry',
                "remarks" => $remarks,
                "arrivaldate" => null,
                "stageid" => 1,
                "tagid" => 2,
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
    }
    elseif ($sessionData[$phone_number]['flow'] === 'dealership_inquiry') {
        $remarks = "
    Inquiry Type: {$sessionData[$phone_number]['flowtitle']} ,
    Pincode: {$sessionData[$phone_number]['pincode']} , \n
    Firm Name: {$sessionData[$phone_number]['companyname']} , \n
    Current Supplier: {$sessionData[$phone_number]['supplier']} , \n
    Onboarding Time: {$sessionData[$phone_number]['onbordtime']} 
";

        writeLog('------------------------------------------------------------------------------------------------');
        writeLog($remarks);
        writeLog('------------------------------------------------------------------------------------------------');
        $data = [
            "menuName" => "New Lead Menu",
            "leadDetails" => [
                "companyname" => $sessionData[$phone_number]['companyname'] ?? 'Whatsapp',
                "email" =>  null,
                "contactno" => $sessionData[$phone_number]['phonenumber'] ?? $phone_number,
                "whatsappno" => $sessionData[$phone_number]['phonenumber'] ?? $phone_number,
                "website" => null,
                "country" =>  null,
                "state" => $sessionData[$phone_number]['state'] ?? '',
                "city" => $sessionData[$phone_number]['city'] ?? '',
                "address" => null,
                "managername" => $sessionData[$phone_number]['username'] ?? null,
                "manageremail" => null,
                "managercontactno" => null,
                "managerwhatsappno" => null,
                "instagramlink" => null,
                "facebooklink" => null,
                "linkedinlink" => null,
                "leadsource" => 'Chatbot - ' . $sessionData[$phone_number]['flowtitle'] ?? 'Chatbot Inquiry',
                "remarks" => $remarks,
                "arrivaldate" => null,
                "stageid" => 1,
                "tagid" => 2,
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
    }
    elseif ($sessionData[$phone_number]['flow'] === 'exportImport_inqiry') {
        $remarks = "
    Inquiry Type: {$sessionData[$phone_number]['flowtitle']} ,
    Company Name: {$sessionData[$phone_number]['companyname'] } ,
    Target Country: {$sessionData[$phone_number]['countryname']} , \n
    Email: {$sessionData[$phone_number]['email']} , \n
    Associated Brand: {$sessionData[$phone_number]['brandname']}
";

        writeLog('------------------------------------------------------------------------------------------------');
        writeLog($remarks);
        writeLog('------------------------------------------------------------------------------------------------');
        $data = [
            "menuName" => "New Lead Menu",
            "leadDetails" => [
                "companyname" => $sessionData[$phone_number]['companyname'] ?? 'Whatsapp',
                "email" =>  $sessionData[$phone_number]['email'] ?? null,
                "contactno" => $sessionData[$phone_number]['phonenumber'] ?? $phone_number,
                "whatsappno" => $sessionData[$phone_number]['phonenumber'] ?? $phone_number,
                "website" => null,
                "country" =>  $sessionData[$phone_number]['countryname'] ?? null,
                "state" => null,
                "city" => null,
                "address" => null,
                "managername" => $sessionData[$phone_number]['username'] ?? null,
                "manageremail" => null,
                "managercontactno" => null,
                "managerwhatsappno" => null,
                "instagramlink" => null,
                "facebooklink" => null,
                "linkedinlink" => null,
                "leadsource" => 'Chatbot - ' . $sessionData[$phone_number]['flowtitle'] ?? 'Chatbot Inquiry',
                "remarks" => $remarks,
                "arrivaldate" => null,
                "stageid" => 1,
                "tagid" => 2,
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
    }

    // 🟨 Only proceed if $data is not empty
    if (!empty($data)) {
        writeLog('------------------------------------------------------------------------------------------------');
        writeLog($data);
        writeLog('------------------------------------------------------------------------------------------------');

        $payload = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        // $response = curl_exec($ch);
        $response = false;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            writeLog("cURL error while posting to Ventas: $error");
        } else {
            writeLog("Posted to Ventas (HTTP $httpCode): $response");
        }

        curl_close($ch);
    } else {
        writeLog("No data prepared for Ventas API; flow was not 'product_inquiry'.");
    }
}
function isValidBrandName($brandName)
{
    return preg_match("/^[a-zA-Z\s]{3,}$/", $brandName);
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
writeLog($data['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name']);
$username = $data['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'];
// $phonenumber = $data['entry'][0]['changes'][0]['value']['contacts'][0]['wa_id'];

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
            "text" => "Hi $username 👋  How may I assist you today ?"
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
                            "description" => "Ask about tile designs or stock availability."

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
//Products / Tiles Inquiry
$tilesSelectionTemplate = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
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
                            "description" => "Find tiles for kitchen, bathroom, outdoors, etc."
                        ],
                        [
                            "id" => "search_by_size",
                            "title" => "By Size",
                            "description" => "Tiles by size like 600x600mm, 800x1600mm."
                        ],
                        [
                            "id" => "search_by_surface",
                            "title" => "By Surface",
                            "description" => "Tiles by surface type — glossy, matte, etc."
                        ],
                        [
                            "id" => "search_by_look",
                            "title" => "By Look & Feel",
                            "description" => "Tiles by style, texture, and look."
                        ]
                    ]
                ]
            ]
        ]
    ]
];
$search_by_area = [
    "messaging_product" => "whatsapp",
    "to" => "917096305498",
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => "By Area"
        ],
        "body" => [
            "text" => "Thank you! Please choose Area"
        ],
        "footer" => [
            "text" => ""
        ],
        "action" => [
            "button" => "Choose Area",
            "sections" => [
                [
                    "title" => "Search Options",
                    "rows" => [
                        [
                            "id" => "Living Room",
                            "title" => "Living Room",
                            "description" => "Living Room"
                        ],
                        [
                            "id" => "Bathroom",
                            "title" => "Bathroom",
                            "description" => "Bathroom"
                        ],
                        [
                            "id" => "40X80CM",
                            "title" => "Bedroom",
                            "description" => "Bedroom"
                        ],
                        [
                            "id" => "Kitchen",
                            "title" => "Kitchen",
                            "description" => "Kitchen"
                        ],
                        [
                            "id" => "Balcony",
                            "title" => "Balcony",
                            "description" => "Balcony"
                        ],
                        [
                            "id" => "Outdoor",
                            "title" => "Outdoor",
                            "description" => "Outdoor"
                        ]
                    ],
                ]
            ]
        ]
    ]
];
$search_by_size = [
    "messaging_product" => "whatsapp",
    "to" => "917096305498",
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => "By Size"
        ],
        "body" => [
            "text" => "Thank you! Please choose Size"
        ],
        "footer" => [
            "text" => ""
        ],
        "action" => [
            "button" => "Choose Size",
            "sections" => [
                [
                    "title" => "Search Options",
                    "rows" => [
                        [
                            "id" => "20X120CM",
                            "title" => "20X120 CM",
                            "description" => "20X120 CM"
                        ],
                        [
                            "id" => "30X60CM",
                            "title" => "30X60 CM",
                            "description" => "30X60 CM"
                        ],
                        [
                            "id" => "40X80CM",
                            "title" => "40X80 CM",
                            "description" => "40X80 CM"
                        ],
                        [
                            "id" => "60X120CM",
                            "title" => "60X120 CM",
                            "description" => "60X120 CM"
                        ],
                        [
                            "id" => "60X60CM",
                            "title" => "60X60 CM",
                            "description" => "60X60 CM"
                        ],
                        [
                            "id" => "80X80CM",
                            "title" => "80X80 CM",
                            "description" => "80X80 CM "
                        ]
                    ],
                ]
            ]
        ]
    ]
];
$search_by_surface = [
    "messaging_product" => "whatsapp",
    "to" => "917096305498",
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => "By Surface"
        ],
        "body" => [
            "text" => "Thank you! Please choose Surface"
        ],
        "footer" => [
            "text" => ""
        ],
        "action" => [
            "button" => "Choose Surface",
            "sections" => [
                [
                    "title" => "Search Options",
                    "rows" => [
                        [
                            "id" => "glit",
                            "title" => "Glit",
                            "description" => "Glit"
                        ],
                        [
                            "id" => "glossy",
                            "title" => "Glossy",
                            "description" => "Glossy"
                        ],
                        [
                            "id" => "matt",
                            "title" => "Matt",
                            "description" => "Matt"
                        ],
                        [
                            "id" => "matte_x",
                            "title" => "Matte-X",
                            "description" => "Matte-X "
                        ],
                        [
                            "id" => "shine_structured",
                            "title" => "Shine Structured",
                            "description" => "Shine Structured "
                        ],
                        [
                            "id" => "structured",
                            "title" => "Structured",
                            "description" => "Structured "
                        ],
                        [
                            "id" => "textured_matt",
                            "title" => "Textured Matt",
                            "description" => "Textured Matt "
                        ]
                    ],
                ]
            ]
        ]
    ]
];
$search_by_look = [
    "messaging_product" => "whatsapp",
    "to" => "917096305498",
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => "Look & feel"
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
                            "id" => "Concrete",
                            "title" => "Concrete",
                            "description" => "Concrete"
                        ],
                        [
                            "id" => "Decorative",
                            "title" => "Decorative",
                            "description" => "Decorative"
                        ],
                        [
                            "id" => "Marble",
                            "title" => "Marble",
                            "description" => "Marble"
                        ],
                        [
                            "id" => "Rustic",
                            "title" => "Rustic ",
                            "description" => "Rustic "
                        ],
                        [
                            "id" => "Solid",
                            "title" => "Solid ",
                            "description" => "Solid "
                        ],
                        [
                            "id" => "Stone",
                            "title" => "Stone ",
                            "description" => "Stone "
                        ],
                        [
                            "id" => "Wood",
                            "title" => "Wood ",
                            "description" => "Wood "
                        ]
                    ],
                ]
            ]
        ]
    ]
];
$ask_squarefeet = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Got it! 🧮\n\nPlease tell us how much you require (in square feet):"
    ]
];
$thankyou = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Thank you! 🙌\n\nOne of our team members will call you shortly to assist further.\nFeel free to explore more: https://lorencesurfaces.com"
    ]
];
// Delarship templates
$askCompanyName = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Thank you! 🙏\n\nCould you please share your Firm or Company Name with us?"
    ]
];
$askOtherSupplier = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Are you currently sourcing from any other supplier?\nIf yes, please mention the name – this helps us serve you better. 😊"
    ]
];
$askOnboardTiming = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "interactive",
    "interactive" => [
        "type" => "button",
        "body" => [
            "text" => "When are you planning to onboard a new supplier?"
        ],
        "action" => [
            "buttons" => [
                [
                    "type" => "reply",
                    "reply" => [
                        "id" => "onboard_immediate",
                        "title" => "1️⃣ Immediate"
                    ]
                ],
                [
                    "type" => "reply",
                    "reply" => [
                        "id" => "onboard_later",
                        "title" => "2️⃣ Later"
                    ]
                ]
            ]
        ]
    ]
];
$dealershipThankYou = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Thank you for your interest in partnering with us! 🙏\n\nOur support team will call you shortly.\n\nMeanwhile, check out our collection:\n🌐 https://lorencesurfaces.com"
    ]
];
// Export/Import templates
$askCountry = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Awesome, thanks! 🌍 *Which country* are you looking to *import* from or *export* to?"
    ]
];
$askEmail = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Got it! could you please share your email address? 📧"
    ]
];
$askBrands = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "To help us serve you better, are you *currently working* with any *specific tile brands?* Let us know! 🏷️"
    ]
];
$exportThankYou = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Thank you so much for sharing the details! 🙏 Our export team will be in touch with you shortly.\n\nIn the meantime, feel free to explore our global-ready tile collection:\n🌐 https://lorencesurfaces.com"
    ]
];
// Error messages
$errorMessage = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "❌ Please enter a valid 6-digit pincode."
    ]
];

// Validation template
$invalid_option_try_again = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "❌ Sorry, we couldn't process your selection. Please try again by selecting a valid option from the list."
    ]
];
$invalid_response_prompt = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "⚠️ Invalid response. Please select an option from the list shown."
    ]
];
$maximum_attempts_reached = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "🚫 You have reached the maximum number of attempts. Thank you for your time."
    ]
];
$invalidSquareFeetMessage = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "❗ Please enter valid square feet (e.g., 500 or 500*500)."
    ]
];
$invalid_interactive_response = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "⚠️ Invalid response. Please tap one of the buttons provided to proceed. Avoid typing your answer."
    ]
];
$invalid_companyname_response = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "⚠️ The *company name* you entered is *invalid*. Please enter a *valid Compny name*."
    ]
];
$retryMessageCountry = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "*❌ Invalid country name.*\nPlease enter a valid country name like *India*, *USA*, etc."
    ]
];
$retryMessageEmail = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "*❌ Invalid email address.*\nPlease enter a valid email like *example@email.com*"
    ]
];
$retryMessageBrand = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "*❌ Invalid brand name.*\nPlease enter a valid brand name ."
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


        // $sessionData[$phone_number] = ['stage' => 0, 'flow' => ''];
        file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
    } else {
        writeLog("User already exists in session. Skipping inquiry list.");
    }
}

// Handle list reply
if (isset($entry['interactive']['list_reply']) && empty($sessionData[$phone_number]['flow'])) {
    $reply = $entry['interactive']['list_reply'];
    $reply_id = $reply['id'] ?? '';
    $reply_title = $reply['title'] ?? '';
    $reply_description = $reply['description'] ?? '';


    writeLog("Interactive reply selected: $reply_id");

    $sessionData[$phone_number] = [
        'stage' => 1,
        'flow' => $reply_id,
        'flowtitle' => $reply_title
    ];
    $sessionData[$phone_number]['phonenumber'] = $phone_number;
    $sessionData[$phone_number]['username'] = $username;
    writeLog($sessionData[$phone_number]['stage']);
    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
}


if (!empty($sessionData[$phone_number]['flow'])) {
    $stage = $sessionData[$phone_number]['stage'] ?? 0;
    $flow = $sessionData[$phone_number]['flow'] ?? '';

    if ($flow === "product_inquiry") {
        switch ($stage) {
            case 1:
                sendWhatsAppMessage($accessToken, "917096305498", "ask_pincode", $version, $phone_number_id);
                writeLog("Message senddned successfully");

                $message = strtolower(trim($entry['text']['body']));
                writeLog("message getted after pincode");

                $sessionData[$phone_number]['stage'] = 2;
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
                            $sessionData[$phone_number]['invalid_attempts'] = 0;
                        } else {
                            $sessionData[$phone_number]['invalid_attempts'] = ($sessionData[$phone_number]['invalid_attempts'] ?? 0) + 1;

                            if ($sessionData[$phone_number]['invalid_attempts'] >= 2) {
                                sendWhatsAppTextMessage($accessToken, $phone_number, $maximum_attempts_reached, $version, $phone_number_id);
                                unset($sessionData[$phone_number]);
                            } else {
                                sendWhatsAppTextMessage($accessToken, $phone_number, $errorMessage, $version, $phone_number_id);
                                $sessionData[$phone_number]['stage'] = 2;
                            }
                        }
                    } else {
                        $sessionData[$phone_number]['invalid_attempts'] = ($sessionData[$phone_number]['invalid_attempts'] ?? 0) + 1;

                        if ($sessionData[$phone_number]['invalid_attempts'] >= 2) {
                            sendWhatsAppTextMessage($accessToken, $phone_number, $maximum_attempts_reached, $version, $phone_number_id);
                            unset($sessionData[$phone_number]);
                        } else {
                            sendWhatsAppTextMessage($accessToken, $phone_number, $errorMessage, $version, $phone_number_id);
                            $sessionData[$phone_number]['stage'] = 2;
                        }
                    }

                    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                }
                break;
            case 3:
                writeLog("Stage 3: Processing tile selection...");

                if (isset($entry['interactive']) && isset($entry['interactive']['list_reply'])) {
                    $tiles = $entry['interactive']['list_reply'];
                    $tiles_id = $tiles['id'] ?? '';
                    $tiles_title = $tiles['title'] ?? '';
                    $tiles_description = $tiles['description'] ?? '';

                    writeLog("Stage 3: Selected tiles type: $tiles_title");

                    $sessionData[$phone_number]['tiles_title'] = $tiles_title;

                    $templateMap = [
                        'search_by_area' => $search_by_area,
                        'search_by_size' => $search_by_size,
                        'search_by_surface' => $search_by_surface,
                        'search_by_look' => $search_by_look
                    ];

                    if (array_key_exists($tiles_id, $templateMap)) {
                        sendWhatsAppTextMessage($accessToken, $phone_number, $templateMap[$tiles_id], $version, $phone_number_id);
                        $sessionData[$phone_number]['stage'] = 4;
                        $sessionData[$phone_number]['invalid_attempts'] = 0;
                    } else {
                        handleMaxAttempts($sessionData, $phone_number, 2, $maximum_attempts_reached, $invalid_response_prompt, 3, $accessToken, $version, $phone_number_id);
                    }
                } else {
                    handleMaxAttempts($sessionData, $phone_number, 2, $maximum_attempts_reached, $invalid_response_prompt, 3, $accessToken, $version, $phone_number_id);
                }

                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                break;


            case 4:
                writeLog("Stage 4: Selected Look and Feel:");

                if (
                    isset($entry['interactive']) &&
                    (isset($entry['interactive']['button_reply']) || isset($entry['interactive']['list_reply']))
                ) {
                    $sessionData[$phone_number]['tile_type'] = $entry['interactive']['list_reply']['title'];
                    sendWhatsAppTextMessage($accessToken, $phone_number, $ask_squarefeet, $version, $phone_number_id);

                    $sessionData[$phone_number]['stage'] = 5;
                    $sessionData[$phone_number]['invalid_attempts'] = 0;
                } else {
                    writeLog("User did not reply with interactive button at stage 4.");
                    handleMaxAttempts($sessionData, $phone_number, 2, $maximum_attempts_reached, $invalid_response_prompt, 4, $accessToken, $version, $phone_number_id);
                }

                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                break;

            case 5:
                writeLog("Stage 5: square feet entered:");

                $squareFeet = trim($entry['text']['body']);


                // Check if the input has at least one digit and not just special characters
                if (preg_match('/[a-zA-Z0-9]/', $squareFeet)) {
                    $sessionData[$phone_number]['squre_feet'] = $squareFeet;
                    $sessionData[$phone_number]['stage'] = 6;
                    $sessionData[$phone_number]['invalid_attempts'] = 0;

                    postDataToVentas(
                        $accessToken,
                        $phone_number,
                        $sessionData,
                        $version,
                        $phone_number_id,
                        $file
                    );

                    sendWhatsAppTextMessage($accessToken, $phone_number, $thankyou, $version, $phone_number_id);
                } else {
                    writeLog("Invalid square feet input: $squareFeet");
                    handleMaxAttempts(
                        $sessionData,
                        $phone_number,
                        2,
                        $maximum_attempts_reached,
                        $invalidSquareFeetMessage,
                        4,
                        $accessToken,
                        $version,
                        $phone_number_id
                    );
                }

                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                break;
        }
    } elseif ($flow === 'dealership_inquiry') {
        switch ($stage) {
            case 1:
                sendWhatsAppMessage($accessToken, "917096305498", "ask_pincode", $version, $phone_number_id);
                writeLog("Message senddned successfully");

                $message = $entry['text']['body'];
                writeLog("message getted after pincode");

                $sessionData[$phone_number]['stage'] = 2;
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                echo "step 2";
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

                            sendWhatsAppTextMessage($accessToken, $phone_number, $askCompanyName, $version, $phone_number_id);
                            $sessionData[$phone_number]['stage'] = 3;
                            $sessionData[$phone_number]['invalid_attempts'] = 0;
                        } else {
                            handleMaxAttempts(
                                $sessionData,
                                $phone_number,
                                2,
                                $maximum_attempts_reached,
                                $errorMessage,
                                2,
                                $accessToken,
                                $version,
                                $phone_number_id
                            );
                        }
                    } else {
                        handleMaxAttempts(
                            $sessionData,
                            $phone_number,
                            2,
                            $maximum_attempts_reached,
                            $errorMessage,
                            2,
                            $accessToken,
                            $version,
                            $phone_number_id
                        );
                    }

                    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                }
                break;

            case 3:
                $message = trim($entry['text']['body']);

                if (preg_match('/[a-zA-Z0-9]/', $message)) {
                    $sessionData[$phone_number]['companyname'] = $message;
                    sendWhatsAppTextMessage($accessToken, $phone_number, $askOtherSupplier, $version, $phone_number_id);
                    writeLog("Company name valid: $message");

                    $sessionData[$phone_number]['stage'] = 4;
                    $sessionData[$phone_number]['invalid_attempts'] = 0;
                } else {
                    handleMaxAttempts(
                        $sessionData,
                        $phone_number,
                        2,
                        $maximum_attempts_reached,
                        $invalid_response_prompt,
                        3,
                        $accessToken,
                        $version,
                        $phone_number_id
                    );
                }

                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                break;

            case 4:
                $message = trim($entry['text']['body']);

                if (preg_match('/[a-zA-Z0-9]/', $message)) {
                    $sessionData[$phone_number]['supplier'] = $message;
                    sendWhatsAppTextMessage($accessToken, $phone_number, $askOnboardTiming, $version, $phone_number_id);
                    writeLog("Supplier input valid: $message");

                    $sessionData[$phone_number]['stage'] = 5;
                    $sessionData[$phone_number]['invalid_attempts'] = 0;
                } else {
                    handleMaxAttempts(
                        $sessionData,
                        $phone_number,
                        2,
                        $maximum_attempts_reached,
                        $invalid_response_prompt,
                        4,
                        $accessToken,
                        $version,
                        $phone_number_id
                    );
                }

                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                break;

            case 5:
                if (isset($entry['interactive']['button_reply']['title'])) {
                    $message = $entry['interactive']['button_reply']['title'];
                    writeLog("Stage 5 reply: $message");

                    $sessionData[$phone_number]['onbordtime'] = $message;
                    sendWhatsAppTextMessage($accessToken, $phone_number, $dealershipThankYou, $version, $phone_number_id);
                    writeLog("Delarship Flow Completed");

                    $sessionData[$phone_number]['stage'] = 6;
                    $sessionData[$phone_number]['invalid_attempts'] = 0;
                      postDataToVentas(
                        $accessToken,
                        $phone_number,
                        $sessionData,
                        $version,
                        $phone_number_id,
                        $file
                    );
                    completeAndClearSession($accessToken, $phone_number, $sessionData, $version, $phone_number_id, $file);
                } else {
                    handleMaxAttempts(
                        $sessionData,
                        $phone_number,
                        2,
                        $maximum_attempts_reached,
                        $invalid_response_prompt,
                        5,
                        $accessToken,
                        $version,
                        $phone_number_id
                    );
                }

                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                break;
        }
    } elseif ($flow === 'exportImport_inqiry') {
        switch ($stage) {
            case 1:
                sendWhatsAppTextMessage($accessToken, $phone_number, $askCompanyName, $version, $phone_number_id);
                writeLog("Message senddned successfully");

                $message = strtolower(trim($entry['text']['body']));
                writeLog("message getted after company name");

                writeLog($message);

                $sessionData[$phone_number]['stage'] = 2;
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                echo "step 2";
                break;

            case 2:
                $message = trim($entry['text']['body']);

                if (preg_match('/[a-zA-Z0-9]/', $message)) {
                    // Valid company name
                    $sessionData[$phone_number]['companyname'] = $message;
                    writeLog("------------------------------------ for STORE ----------------");

                    sendWhatsAppTextMessage($accessToken, $phone_number, $askCountry, $version, $phone_number_id);
                    writeLog("Company name valid: $message");

                    $sessionData[$phone_number]['stage'] = 3;
                    $sessionData[$phone_number]['invalid_attempts'] = 0;
                } else {
                    // Invalid company name - handle invalid response
                    handleMaxAttempts(
                        $sessionData,
                        $phone_number,
                        2, // max attempts
                        $maximum_attempts_reached, // template name
                        $invalid_companyname_response,  // template name
                        2, // retry current stage
                        $accessToken,
                        $version,
                        $phone_number_id
                    );
                }

                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                echo "step 3";
                break;

            case 3:
                writeLog("----- Inside Stage 3 -----");
                $message = trim($entry['text']['body']);
                $lowerMessage = strtolower($message);

                if (!$message) {
                    writeLog("❌ Invalid country name: $message");
                    // Call the common handler for retry or failure
                    handleMaxAttempts(
                        $sessionData,
                        $phone_number,
                        3, // Max attempts
                        $maximum_attempts_reached,
                        $retryMessageCountry,
                        3,
                        $accessToken,
                        $version,
                        $phone_number_id
                    );
                    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                    break;
                }

                $sessionData[$phone_number]['countryname'] = $message;
                $sessionData[$phone_number]['invalid_attempts'] = 0;

                sendWhatsAppTextMessage($accessToken, $phone_number, $askEmail, $version, $phone_number_id);
                writeLog("📧 Email question sent.");

                $sessionData[$phone_number]['stage'] = 4;
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                echo "step 4";
                break;


            case 4:
                writeLog("----- Inside Stage 4 -----");

                $message = trim($entry['text']['body']);
                $lowerMessage = strtolower($message);

                // 📧 Validate email format
                if (!$message) {
                    writeLog("❌ Invalid email address: $message");

                    // ❌ Email validation failed response template


                    // Call common max attempt handler
                    handleMaxAttempts(
                        $sessionData,
                        $phone_number,
                        3, // Max attempts allowed
                        $maximum_attempts_reached,
                        $retryMessageEmail,
                        4,
                        $accessToken,
                        $version,
                        $phone_number_id
                    );

                    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                    break;
                }

                // ✅ Valid email
                $sessionData[$phone_number]['email'] = $message;
                // Reset invalid_attempts for the next stage
                $sessionData[$phone_number]['invalid_attempts'] = 0;

                sendWhatsAppTextMessage($accessToken, $phone_number, $askBrands, $version, $phone_number_id);
                writeLog("📦 Brand question sent.");

                $sessionData[$phone_number]['stage'] = 5;
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                echo "step 5";
                break;

            case 5:
                writeLog("----- Inside Stage 5 -----");

                $message = trim($entry['text']['body']);

                // 🧪 Validate brand name
                if (!$message) {
                    writeLog("❌ Invalid brand name: $message");

                    handleMaxAttempts(
                        $sessionData,
                        $phone_number,
                        3, // Max retries
                        $maximum_attempts_reached,
                        $retryMessageBrand,
                        5, // Stay in same stage
                        $accessToken,
                        $version,
                        $phone_number_id
                    );

                    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                    break;
                }

                // ✅ Brand name is valid
                $sessionData[$phone_number]['brandname'] = $message;
                $sessionData[$phone_number]['invalid_attempts'] = 0;
                $sessionData[$phone_number]['stage'] = 6;

                sendWhatsAppTextMessage($accessToken, $phone_number, $exportThankYou, $version, $phone_number_id);
                writeLog("🎉 Export/Import flow completed.");

                // Send summary & clear session
                postDataToVentas(
                        $accessToken,
                        $phone_number,
                        $sessionData,
                        $version,
                        $phone_number_id,
                        $file
                    );
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                break;
        }
    } elseif ($flow == 'request_call_back') {
        switch ($stage) {
            case 1:
                sendWhatsAppMessage($accessToken, "917096305498", "requestcallbackthankyou ", $version, $phone_number_id);
                writeLog("Message senddned successfully");

                $message = strtolower(trim($entry['text']['body']));
                writeLog("message geetsed last Request");

                $sessionData[$phone_number]['stage'] = 2;
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                echo "step 1";
                break;
        }
    }
} else {
}
