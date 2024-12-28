<?php
require "conn.php";

$type_service = isset($_POST['type_service']) ? $_POST['type_service'] : '';
$data = isset($_POST['data']) ? $_POST['data'] : '';

if (!empty($type_service) && !empty($data)) {
    $sql = "SELECT time, free, price FROM reservation WHERE type_service = ? AND data = ? ORDER BY time ASC";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ss", $type_service, $data);
        $stmt->execute();
        $result = $stmt->get_result();

        $output = array();
        while ($row = $result->fetch_assoc()) {
            $output[] = $row;
        }

        echo json_encode($output);
        $stmt->close();
    } else {
        echo json_encode(array("error" => "Failed to prepare statement"));
    }
} else {
    echo json_encode(array("error" => "Invalid input"));
}

$conn->close();
?>
