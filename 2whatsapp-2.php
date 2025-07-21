<?php
function sendWhatsAppMessage($receiver_number, $templateName, $authToken , $version, $phone_number_id)
{
    $url = "https://graph.facebook.com/{$version}/597358536789220/messages";
    echo $url;

    $data = [
        "messaging_product" => "whatsapp",
        "to" => $receiver_number,
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
    curl_close($ch);

    return $response;
}

function sendWhatsAppTextMessage($authToken, $receiver_number, $message, $version, $phone_number_id)
{
    $url = "https://graph.facebook.com/{$version}/{$phone_number_id}/messages";
    
    $data = [
        "messaging_product" => "whatsapp",
        "recipient_type" => "individual",
        "to" => "917096305498",
        "type" => "text",
        "text" => [
            "preview_url" => false,
            "body" => $message
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
    curl_close($ch);
    
    return $response;
}


$phone_number_id = 597358536789220;
$token = "Bearer EAAQWzLGgTPkBO9W23Ou6DXofUWdL1AZCqYTo16m30RrXzC9FEy4avF8mSM9vRFwW6yaGOWUtiuhyErwzeaRoSAZCnQ4wEORkhL2IIY850WHDNjPGZBFp00ZCySZB1yhBD5VLaSmzbZBoCSYa8DGD2GIGi3k3aDroy6kcvoj3YONtJEWZB9ycrWCvito2DJQ0VxpZCt2mpYsSeI3WG48ALAZDZD";
$version = "v22.0";
$receiver_number = 917435863672;
$templateName = "user_name";

if (isset($_POST['send'])) {
    $to = "917435863672"; // Replace with dynamic user input if needed
    $templateName = "user_name";

    $response = sendWhatsAppMessage($to, $templateName, $token, $version , $phone_number_id);
    // $response1 = sendWhatsAppTextMessage($token, "917096305498", "nathi kevo", $version , $phone_number_id);
    // echo " Message sent successfully! $response1 ";
    echo " Message sent successfully! $response ";
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send WhatsApp Message</title>
</head>
<body>
    <form method="post">
        <button type="submit" name="send">Send Message</button>
    </form>
</body>
</html>