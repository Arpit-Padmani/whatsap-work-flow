<?php
function writeLog($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}
$hubVerifyToken = 'lorence_surfaces_support_workflow';
$accessToken = 'EAAcusGlAK00BPY6oVnGck2W3Ts0jwov0njtQirIYmBPTWKUyAoUfHxpPIyZAPFHSguLcBbfjHgPUYVZA2ANy3IECjocMz1uGyyifR126F1VHFRoZCTGVfz51G5R4ZCg8BC9uUJ2YQADQhaujdhxtFC0Tfk65SY5ZBlFY06NQd6TwzUDxwIbj0ksenB3x6f0v7V5h53zOzSZAorq90nThpuESzvVLExZAHPuIGZBWZCEREBAZDZD';

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
$phone_number_id = 713193288549152;

$messageData = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;

$file = 'session.json';

if ($messageData['type'] === 'text') {
    $text = $messageData['text']['body'] ?? '';
    writeLog("Text message received: $text → ignored.");
    exit;
}

if ($messageData['type'] === 'interactive' 
    && isset($messageData['interactive']['button_reply']['id'])) {

    $buttonId = $messageData['interactive']['button_reply']['id'];
    writeLog("Button reply received: $buttonId");

    if ($buttonId === 'ok') {
        $file = __DIR__ . '/session.json';
        if (file_exists($file)) {
            $sessionData = json_decode(file_get_contents($file), true);

            if (isset($sessionData[$phone_number])) {
                // Only update the latest pending message
                foreach ($sessionData[$phone_number] as $index => $msg) {
                    if (isset($msg['isReplied']) && $msg['isReplied'] === false) {
                        $sessionData[$phone_number][$index]['isReplied'] = true;
                        writeLog("Marked as replied for $phone_number (index $index)");
                        break; // stop after first match
                    }
                }
                file_put_contents($file, json_encode($sessionData, JSON_PRETTY_PRINT));
            } else {
                writeLog("No session data found for $phone_number");
            }
        } else {
            writeLog("session.json not found!");
        }
    } else {
        writeLog("Button reply id '$buttonId' ignored.");
    }
} else {
    writeLog("Non-text/Non-button message ignored.");
}

// if (!file_exists($file)) {
//     file_put_contents($file, json_encode([]));
// }