<?php
include('whatsappSendMsg.php');
require_once 'helpers.php';

$hubVerifyToken = 'lorence_surfaces_workflow';
$accessToken = 'EAAR8YYnJJZAIBPL9RwVPSKJzwdvzfkZCLwJR5ZBIrrhAaMMzxNBZB9dW6ZALZA3Bj35TcQRpJYgrQHXwjZBu5sFpk0A4YQTYEvxF15kwdfyIhTdJTpq2vMBM7SP6DnM3xrhSmLB05uycLGAoUonFBJ9RkSzPAnWBer81dRKNwu7LZByGhLg6SSdzUbqJtKMQdL11qEfYg6balMWmxMZCx258tToxfLRkt0FYwP125kJ5EkAZDZD';

$logFile = __DIR__ . '/webhook_session.log';

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

$entry = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;

$file = 'session_data.json';

if (!file_exists($file)) {
    file_put_contents($file, json_encode([]));
}
$userMessageII = '';
$sessionData = json_decode(file_get_contents($file), true);
include 'templates.php';
if (isset($entry['text']['body'])) {
    $userMessage = trim($entry['text']['body']);

    if (!isset($sessionData[$phone_number])) {
        // Stage 0: New user
        writeLog("New user detected. Sending welcome message.");
        sendWhatsAppTextMessage($accessToken, $phone_number, $welcomeTemplate, $version, $phone_number_id);
        sendWhatsAppTextMessage($accessToken, $phone_number, $askusername, $version, $phone_number_id);

        $sessionData[$phone_number] = [
            'stage' => 0, // Next expected: user name
        ];
        file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
    } else {
        $stage = $sessionData[$phone_number]['stage'];
        if ($stage === 0) {
            // Stage 1: User entered their name
            $sessionData[$phone_number]['name'] = $userMessage;
            $sessionData[$phone_number]['stage'] = 1;
            $userMessageII = $sessionData[$phone_number]['name'];


            $personalizedTemplate = $inqueryTemplate;
            $personalizedTemplate['to'] = $phone_number;
            $personalizedTemplate['interactive']['body']['text'] = str_replace(
                '{{user_name}}',
                $userMessageII,
                $inqueryTemplate['interactive']['body']['text']
            );


            writeLog("User entered name: $userMessageII. Sending inquiry template.");
            sendWhatsAppTextMessage($accessToken, $phone_number, $personalizedTemplate, $version, $phone_number_id);

            file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
        } else {
            writeLog("User already in session, current stage: $stage. No action taken.");
        }
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
    writeLog('-------------------------------------------');

    if ($flow === "product_inquiry") {
        switch ($stage) {
            case 1:
                sendWhatsAppMessage($accessToken, $phone_number, "ask_pincode", $version, $phone_number_id);
                writeLog("Message senddned successfully");

                $message = strtolower(trim($entry['text']['body'] ?? ''));
                writeLog("message getted after pincode");

                $sessionData[$phone_number]['stage'] = 2;
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                echo "step 1";
                break;

            case 2:
                if (isset($entry['interactive']) && (isset($entry['interactive']['list_reply']) || isset($entry['interactive']['button_reply']))) {
                    if (handleWrongInteractiveReply($stage, $sessionData, $phone_number, $accessToken, $version, $phone_number_id, $file)) break;
                }
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
                        $sessionData[$phone_number]['last_template_sent'] = $tiles_id;
                    } else {
                        handleMaxAttempts($sessionData, $phone_number, 2, $maximum_attempts_reached, $invalid_response_prompt, 3, $accessToken, $version, $phone_number_id);
                    }
                } else {
                    handleMaxAttempts($sessionData, $phone_number, 2, $maximum_attempts_reached, $invalid_response_prompt, 3, $accessToken, $version, $phone_number_id);
                }

                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                break;
            case 4:
                writeLog("Stage 4: Look and Feel Selection");
                if ($sessionData[$phone_number]['invalid_attempts'] >= 2) {
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
                    break;
                }
                if (isset($entry['interactive']) && isset($entry['interactive']['list_reply'])) {
                    $look_value = $entry['interactive']['list_reply']['title'];
                    $look_id = $entry['interactive']['list_reply']['id'];

                    $valid_look_ids = ['Concrete', 'Decorative', 'Marble', 'Rustic', 'Solid', 'Stone', 'Wood', 'glit', 'glossy', 'matt', 'matte_x', 'shine_structured', 'structured', 'textured_matt', '20X120CM', '30X60CM', '40X80CM', '60X120CM', '60X60CM', '80X80CM', 'Living Room', 'Bathroom', 'Bedroom', 'Kitchen', 'Balcony', 'Outdoor'];

                    if (in_array($look_id, $valid_look_ids)) {
                        writeLog("Valid look selected: $look_value");

                        $sessionData[$phone_number]['tile_type'] = $look_value;
                        $sessionData[$phone_number]['stage'] = 5;
                        $sessionData[$phone_number]['invalid_attempts'] = 0;

                        sendWhatsAppTextMessage($accessToken, $phone_number, $ask_squarefeet, $version, $phone_number_id);
                    } else {
                        writeLog("Invalid look id received: $look_id");

                        // Send invalid option message and resend appropriate list
                        sendWhatsAppTextMessage($accessToken, $phone_number, $invalid_option_template, $version, $phone_number_id);

                        $last_menu = $sessionData[$phone_number]['last_template_sent'] ?? 'search_by_look';

                        switch ($last_menu) {
                            case 'search_by_area':
                                sendWhatsAppTextMessage($accessToken, $phone_number, $search_by_area, $version, $phone_number_id);
                                break;
                            case 'search_by_size':
                                sendWhatsAppTextMessage($accessToken, $phone_number, $search_by_size, $version, $phone_number_id);
                                break;
                            case 'search_by_surface':
                                sendWhatsAppTextMessage($accessToken, $phone_number, $search_by_surface, $version, $phone_number_id);
                                break;
                            case 'search_by_look':
                                sendWhatsAppTextMessage($accessToken, $phone_number, $search_by_look, $version, $phone_number_id);
                                break;
                            default:
                                sendWhatsAppTextMessage($accessToken, $phone_number, $search_by_look, $version, $phone_number_id);
                                break;
                        }

                        $sessionData[$phone_number]['invalid_attempts'] = ($sessionData[$phone_number]['invalid_attempts'] ?? 0) + 1;

                        // if ($sessionData[$phone_number]['invalid_attempts'] >= 2) {
                        //     handleMaxAttempts(
                        //         $sessionData,
                        //         $phone_number,
                        //         2,
                        //         $maximum_attempts_reached,
                        //         $invalid_response_prompt,
                        //         4,
                        //         $accessToken,
                        //         $version,
                        //         $phone_number_id
                        //     );
                        // }
                    }
                } else {
                    writeLog("No list_reply found. Resending previous menu.");

                    // No reply — resend the last known menu
                    sendWhatsAppTextMessage($accessToken, $phone_number, "❌ Please choose a valid option from the list below 👇", $version, $phone_number_id);

                    $last_menu = $sessionData[$phone_number]['last_menu'] ?? 'search_by_look';

                    switch ($last_menu) {
                        case 'search_by_area':
                            sendWhatsAppTextMessage($accessToken, $phone_number, $search_by_area, $version, $phone_number_id);
                            break;
                        case 'search_by_size':
                            sendWhatsAppTextMessage($accessToken, $phone_number, $search_by_size, $version, $phone_number_id);
                            break;
                        case 'search_by_surface':
                            sendWhatsAppTextMessage($accessToken, $phone_number, $search_by_surface, $version, $phone_number_id);
                            break;
                        case 'search_by_look':
                            sendWhatsAppTextMessage($accessToken, $phone_number, $search_by_look, $version, $phone_number_id);
                            break;
                        default:
                            sendWhatsAppTextMessage($accessToken, $phone_number, $tilesSelectionTemplate, $version, $phone_number_id);
                            break;
                    }

                    $sessionData[$phone_number]['invalid_attempts'] = ($sessionData[$phone_number]['invalid_attempts'] ?? 0) + 1;
                }

                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                break;

            case 5:
                if ($sessionData[$phone_number]['invalid_attempts'] >= 2) {
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
                    break;
                }
                if (isset($entry['interactive']) && (isset($entry['interactive']['list_reply']) || isset($entry['interactive']['button_reply']))) {
                    if (handleWrongInteractiveReply($stage, $sessionData, $phone_number, $accessToken, $version, $phone_number_id, $file)) break;
                }
                writeLog("Stage 5: square feet entered:");

                $squareFeet = trim($entry['text']['body']);

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

                    $sessionData = [];
                    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                    writeLog("All session data cleared.");
                    sendWhatsAppMessage($accessToken, $phone_number, "product_thankyou ", $version, $phone_number_id);

                    
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
                break;
        }
    } elseif ($flow === 'dealership_inquiry') {
        switch ($stage) {
            case 1:
                sendWhatsAppMessage($accessToken, $phone_number, "ask_pincode", $version, $phone_number_id);
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
                    sendWhatsAppMessage($accessToken, $phone_number, "delarship_thankyou ", $version, $phone_number_id);
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
                    $sessionData = [];
                    file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                    writeLog("All session data cleared.");
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
                sendWhatsAppMessage($accessToken, $phone_number, "export_thankyou ", $version, $phone_number_id);
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
                $sessionData = []; // 👈 this clears everything
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                writeLog("All session data cleared.");
                break;
        }
    } elseif ($flow == 'request_call_back') {
        switch ($stage) {
            case 1:
                sendWhatsAppMessage($accessToken, $phone_number, "requestcallbackthankyou ", $version, $phone_number_id);
                writeLog("Message senddned successfully");
                $message = strtolower(trim($entry['text']['body']));
                writeLog("message geetsed last Request");

                $sessionData[$phone_number]['stage'] = 2;
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                echo "step 1";
                postDataToVentas(
                    $accessToken,
                    $phone_number,
                    $sessionData,
                    $version,
                    $phone_number_id,
                    $file
                );

                // Clear entire session data
                $sessionData = []; // 👈 this clears everything
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
                writeLog("All session data cleared.");
                break;
        }
    }
} else {
}
