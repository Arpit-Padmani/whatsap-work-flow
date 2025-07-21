<!-- <?php
$apiUrl = "https://cqpplefitting.com/webhook/webhook.php"; // Change this to your actual webhook URL

// Function to call the webhook API using cURL
function getWebhookResponse($apiUrl)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec(handle: $ch);
    curl_close($ch);
    return $response;
}

// Fetch the initial response (optional for initial load)
$initialResponse = getWebhookResponse($apiUrl);
echo $initialResponse;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Response Viewer</title>
    <script>
        function checkMessages() {
            fetch("<?php echo $apiUrl; ?>")
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        document.getElementById("messageDisplay").innerHTML =
                            `<h2>Full Webhook Response</h2>
                            <pre>${JSON.stringify(data.full_response, null, 2)}</pre>`;
                    } else {
                        document.getElementById("messageDisplay").innerHTML = "<p>No new messages received.</p>";
                    }
                })
                .catch(error => console.error("Error fetching messages:", error));
        }

        // Auto-load response on page load (optional)
        window.onload = function() {
            checkMessages();
        };
    </script>
</head>
<body>
    <h1>WhatsApp Webhook Response</h1>
    <button onclick="checkMessages()">Refresh Response</button>
    <div id="messageDisplay">
        <h2>Initial Webhook Response</h2>
        <pre><?php echo json_encode(json_decode($initialResponse, true), JSON_PRETTY_PRINT); ?></pre>
    </div>
</body>
</html> -->

<?php
// Sample JSON data (this would come from the POST body in your case)
$jsonData = '{
    "object": "whatsapp_business_account",
    "entry": [
      {
        "id": "555206214347940",
        "changes": [
          {
            "value": {
              "messaging_product": "whatsapp",
              "metadata": {
                "display_phone_number": "15551921195",
                "phone_number_id": "597358536789220"
              },
              "contacts": [
                {
                  "profile": {
                    "name": "Arpit Padmani"
                  },
                  "wa_id": "917096305498"
                }
              ],
              "messages": [
                {
                  "from": "917096305498",
                  "id": "wamid.HBgMOTE3MDk2MzA1NDk4FQIAEhgWM0VCMEUzNTI2NTlGQjQxMUZCNkRFOQA=",
                  "timestamp": "1740216562",
                  "text": {
                    "body": "709630549872"
                  },
                  "type": "text"
                }
              ]
            },
            "field": "messages"
          }
        ]
      }
    ]
  }';
  
  // Decode the JSON data into a PHP associative array
  $data = json_decode($jsonData, true);
  
  // Check if the 'messages' field exists
  if (isset($data['entry'][0]['changes'][0]['value']['messages'])) {
      // Loop through the messages
      foreach ($data['entry'][0]['changes'][0]['value']['messages'] as $message) {
          // Check if the message type is "text"
          if (isset($message['type']) && $message['type'] === 'text') {
              // Extract the value of 'body'
              if (isset($message['text']['body'])) {
                  $body = $message['text']['body'];
                  echo "Message Body: " . $body . "<br>";
              }
          }
      }
  } else {
      echo "No messages found!";
  }
  