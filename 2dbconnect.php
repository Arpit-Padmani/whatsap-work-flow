<?php 
function getDBConnection() 
{ 
    $localhost = '103.76.231.90'; 
    $user = 'cqpplzym_whatsapp_msg'; 
    $password = 'whatsapp@1234567890';
    $dbname = 'cqpplzym_service'; 

    $connection = new mysqli($localhost, $user, $password, $dbname);
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }
    return $connection;
}

function addUserData($phone_number, $name, $mobile,$age) 
{
    $connection = getDBConnection();

    $insert = $connection->prepare("INSERT INTO whatsap_flow (user_mobile, Name, Mobile, age) VALUES (?, ?, ?, ?)");
    if ($insert === false) {
        echo "Prepare failed: " . $connection->error;
    }


    $insert->bind_param("ssss",$phone_number, $name,$mobile,$age);

    if ($insert->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Data inserted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Insert failed', 'error' => $insert->error]);
    }

    // Close statement and connection
    $insert->close();
    $connection->close();
    return "inserted";
}


?>