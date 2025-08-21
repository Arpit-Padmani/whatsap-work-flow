<?php
include 'templates.php';
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
function handleMaxAttempts(&$sessionData, $phone_number, $maxAttempts, $inqueryTemplate, $retryTemplate, $stage, $accessToken, $version, $phone_number_id)
{

    $file = 'session_data.json';


    if (!isset($sessionData[$phone_number]['invalid_attempts'])) {
        $sessionData[$phone_number]['invalid_attempts'] = 0;
    }

    $sessionData[$phone_number]['invalid_attempts']++;

    if ($sessionData[$phone_number]['invalid_attempts'] >= 4) {
        $sessionData = [
            $phone_number => [
                "stage" => 0,
                "name" => $sessionData[$phone_number]['name'] ?? ''
            ]
        ];

        $userMessageII = $sessionData[$phone_number]['name'];
        $personalizedTemplate = $inqueryTemplate;
        $personalizedTemplate['to'] = $phone_number;
        $personalizedTemplate['interactive']['body']['text'] = str_replace(
            '{{user_name}}',
            $userMessageII,
            $inqueryTemplate['interactive']['body']['text']
        );
        sendWhatsAppTextMessage($accessToken, $phone_number, $personalizedTemplate, $version, $phone_number_id);

        file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
        // sendWhatsAppTextMessage($accessToken, $phone_number, $failureTemplate, $version, $phone_number_id);
        // unset($sessionData[$phone_number]);
        return 0; // Clear session on failure
    } else {
        sendWhatsAppTextMessage($accessToken, $phone_number, $retryTemplate, $version, $phone_number_id);
        $sessionData[$phone_number]['stage'] = $stage; // Retry same stage
    }
}
function completeAndClearSession($accessToken, $phone_number, $sessionData, $version, $phone_number_id, $file)
{
    // Format the collected data
    $userData = $sessionData[$phone_number] ?? [];
    writeLog($userData);

    $summary = "✅ Thank you for your response! Here’s what we received:\n\n";
    $summary .= "🧾 Company Name: *" . ($userData['companyname'] ?? '-') . "*\n";
    $summary .= "🌍 Country: *" . ($userData['countryname'] ?? '-') . "*\n";
    $summary .= "✉️ Email: *" . ($userData['email'] ?? '-') . "*\n";
    $summary .= "🏷️ Brand Name: *" . ($userData['brandname'] ?? '-') . "*\n";
    $summary .= "\nWe’ll get in touch with you shortly.";

    $thankYouTemplate = [
        "messaging_product" => "whatsapp",
        "to" => $phone_number,
        "type" => "text",
        "text" => [
            "body" => $summary
        ]
    ];

    // Optional: send summary to user
    // sendWhatsAppTextMessage($accessToken, $phone_number, $thankYouTemplate, $version, $phone_number_id);

    // Log and clear session
    writeLog("Session completed for $phone_number. Clearing session data.");
    unset($sessionData[$phone_number]);

    // Confirm it's unset
    writeLog("Remaining session data: " . json_encode($sessionData));

    // Save updated session data
    if (is_writable($file)) {
        $sessionData = [];
        file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));

        writeLog("File $file is not writable.");
        // file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
    } else {
        writeLog("Error: File $file is not writable.");
    }
}

function postDataToVentas($accessToken, $phone_number, $sessionData, $version, $phone_number_id, $file)
{
    $url = "https://ventas.lorencesurfaces.com/api/WhatsappAPIs/AddLead";

    $data = []; // default empty

    if ($sessionData[$phone_number]['flow'] === 'product_inquiry') {
        $remarks = "
    Name: {$sessionData[$phone_number]['username']} , 
    Inquiry Type: {$sessionData[$phone_number]['flowtitle']} , 
    Pincode: {$sessionData[$phone_number]['pincode']} , 
    Search Preference: {$sessionData[$phone_number]['tiles_title']} , 
    {$sessionData[$phone_number]['tiles_title']} : {$sessionData[$phone_number]['tile_type']} , 
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
                "contactno" => '+' . $sessionData[$phone_number]['phonenumber'] ?? '+' . $phone_number,
                "whatsappno" => '+' . $sessionData[$phone_number]['phonenumber'] ?? '+' . $phone_number,
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
    } elseif ($sessionData[$phone_number]['flow'] === 'dealership_inquiry') {
        $remarks = "
    Inquiry Type: {$sessionData[$phone_number]['flowtitle']} ,
    Pincode: {$sessionData[$phone_number]['pincode']} , 
    Firm Name: {$sessionData[$phone_number]['companyname']} , 
    Current Supplier: {$sessionData[$phone_number]['supplier']} , 
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
                "contactno" => '+' . $sessionData[$phone_number]['phonenumber'] ?? '+' . $phone_number,
                "whatsappno" => '+' . $sessionData[$phone_number]['phonenumber'] ?? '+' . $phone_number,
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
    } elseif ($sessionData[$phone_number]['flow'] === 'exportImport_inqiry') {
        $remarks = "
    Inquiry Type: {$sessionData[$phone_number]['flowtitle']} ,
    Company Name: {$sessionData[$phone_number]['companyname']} ,
    Target Country: {$sessionData[$phone_number]['countryname']} , 
    Email: {$sessionData[$phone_number]['email']} , 
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
                "contactno" => '+' . $sessionData[$phone_number]['phonenumber'] ?? '+' . $phone_number,
                "whatsappno" => '+' . $sessionData[$phone_number]['phonenumber'] ?? '+' . $phone_number,
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
    } elseif ($sessionData[$phone_number]['flow'] === 'request_call_back') {
        $remarks = "
    Inquiry Type: {$sessionData[$phone_number]['flowtitle']} ,
    User Name: {$sessionData[$phone_number]['username']} ,
    Mobile Number:  '+'.{$sessionData[$phone_number]['phonenumber']}
";
        $data = [
            "menuName" => "New Lead Menu",
            "leadDetails" => [
                "companyname" => $sessionData[$phone_number]['username'] ?? 'Whatsapp',
                "email" =>  null,
                "contactno" => '+' . $sessionData[$phone_number]['phonenumber'] ?? '+' . $phone_number,
                "whatsappno" => '+' . $sessionData[$phone_number]['phonenumber'] ?? '+' . $phone_number,
                "website" => null,
                "country" =>  null,
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

        $response = curl_exec($ch);
        // $response = false;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            writeLog("cURL error while posting to Ventas: $error");
            writeLog($response);
        } else {
            writeLog("Posted to Ventas (HTTP $httpCode): $response");
            writeLog('-----------------------------------------------');
            writeLog($response);
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

// Reusable function to handle wrong interactive input


function handleWrongInteractiveReply($stage, $sessionData, $phone_number, $accessToken, $version, $phone_number_id, $file)
{
    $invalid_prompt_templates = [
        2 => [
            "messaging_product" => "whatsapp",
            "to" => $phone_number,
            "type" => "text",
            "text" => [
                "body" => "📍 Please enter your 6-digit pincode."
            ]
        ],
        4 => [
            "messaging_product" => "whatsapp",
            "to" => $phone_number,
            "type" => "text",
            "text" => [
                "body" => "📌 Please enter the tile value (e.g., Glossy, Matte)."
            ]
        ],
        5 => [
            "messaging_product" => "whatsapp",
            "to" => $phone_number,
            "type" => "text",
            "text" => [
                "body" => "📐 Please enter the area in square feet (e.g., 400)."
            ]
        ]
    ];

    $sessionData[$phone_number]['invalid_attempts'] = ($sessionData[$phone_number]['invalid_attempts'] ?? 0) + 1;

    if ($sessionData[$phone_number]['invalid_attempts'] >= 2) {
        handleMaxAttempts(
            $sessionData,
            $phone_number,
            2,
            $GLOBALS['maximum_attempts_reached'],
            $GLOBALS['invalid_response_prompt'],
            $stage,
            $accessToken,
            $version,
            $phone_number_id
        );
    } else {
        // Only send the error and repeat prompt if max attempts not yet reached
        $error_message = "❌ Invalid input. Please type your response as requested.";
        $repeat_prompt = $invalid_prompt_templates[$stage] ?? [
            "messaging_product" => "whatsapp",
            "to" => $phone_number,
            "type" => "text",
            "text" => [
                "body" => "❗ Please enter a valid response."
            ]
        ];

        sendWhatsAppTextMessage($accessToken, $phone_number, $error_message, $version, $phone_number_id);
        sendWhatsAppTextMessage($accessToken, $phone_number, $repeat_prompt, $version, $phone_number_id);
    }

    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
    return true;
}


function resendLastTemplate($accessToken, $phone_number, $version, $phone_number_id, $last_template_sent)
{
    global $search_by_area, $search_by_size, $search_by_surface, $search_by_look, $tilesSelectionTemplate;

    $templateMap = [
        'search_by_area' => $search_by_area,
        'search_by_size' => $search_by_size,
        'search_by_surface' => $search_by_surface,
        'search_by_look' => $search_by_look
    ];

    if (isset($templateMap[$last_template_sent])) {
        $template = $templateMap[$last_template_sent];

        // Decide which function to use based on data type
        if (is_array($template)) {
            sendWhatsAppTextMessage($accessToken, $phone_number, $template, $version, $phone_number_id);
        } else {
            sendWhatsAppMessage($accessToken, $phone_number, $template, $version, $phone_number_id);
        }
    } else {
        sendWhatsAppTextMessage($accessToken, $phone_number, $tilesSelectionTemplate, $version, $phone_number_id);
    }
}


function markMessageAsRead($payload, $accessToken)
{
    // Decode incoming webhook JSON
    $data = $payload;
    // Make sure we have the phone_number_id and the message ID
    if (
        !isset($data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id']) ||
        !isset($data['entry'][0]['changes'][0]['value']['messages'][0]['id'])
    ) {
        error_log("Required fields missing in webhook payload");
        return false;
    }

    $phoneNumberId = $data['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'];
    $messageId     = $data['entry'][0]['changes'][0]['value']['messages'][0]['id'];

    // API URL - use dynamic phone number ID
    $url = "https://graph.facebook.com/v23.0/{$phoneNumberId}/messages";

    // Payload to mark message as read
    $postData = [
        "messaging_product" => "whatsapp",
        "status" => "read",
        "message_id" => $messageId
    ];

    // Send request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("cURL error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    return $response;
}

function getAreaUrl(string $area): string
{
    $areaUrls = [
        "Living Room" => "https://lorencesurfaces.com/room/living-room/",
        "Bathroom"   => "https://lorencesurfaces.com/room/bathroom/",
        "Bedroom"    => "https://lorencesurfaces.com/room/bedroom/",
        "Kitchen"    => "https://lorencesurfaces.com/room/kitchen/",
        "Balcony"    => "https://lorencesurfaces.com/room/balcony/",
        "Outdoor"    => "https://lorencesurfaces.com/room/outdoor/",
        "Office"     => "https://lorencesurfaces.com/room/office/"
    ];

    return $areaUrls[$area] ?? "https://lorencesurfaces.com/";
}
