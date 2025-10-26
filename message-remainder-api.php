<?php
header("Content-Type: application/json");
include 'db_connect.php';

class MessageRemainder extends dbconnect
{
    public function insertRemainder($data)
    {
        $mobile_number = $data['mobile_number'] ?? '';
        $company_name = $data['company_name'] ?? '';
        $remainder_message = $data['remainder_message'] ?? '';
        $remainder_date_time = $data['remainder_date_time'] ?? '';

        // Validation
        if (empty($mobile_number) || empty($remainder_message) || empty($remainder_date_time)) {
            return ["status" => "error", "message" => "Missing required fields"];
        }

        // Escape data to prevent SQL injection
        $mobile_number = $this->connection->real_escape_string($mobile_number);
        $company_name = $this->connection->real_escape_string($company_name);
        $remainder_message = $this->connection->real_escape_string($remainder_message);
        $remainder_date_time = $this->connection->real_escape_string($remainder_date_time);

        // Insert query
        $insert = "
            INSERT INTO lorence_reminder 
            (mobile_number, company_name, remainder_message, remainder_date_time, status, created_at)
            VALUES ('$mobile_number', '$company_name', '$remainder_message', '$remainder_date_time', 1, NOW())
        ";

        // Execute query using your style
        $result = $this->connection->query($insert);

        if ($result) {
            return ["status" => "success", "message" => "Message scheduled successfully"];
        } else {
            return ["status" => "error", "message" => $this->connection->error];
        }
    }
}

// Main logic
$data = json_decode(file_get_contents("php://input"), true);
$obj = new MessageRemainder();
$response = $obj->insertRemainder($data);

echo json_encode($response);
?>
