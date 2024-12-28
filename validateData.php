<?php

require "conn.php";
$Email_Address = $_POST["Email_Address"];
$Password = $_POST["Password"];

$mysqli_query = "SELECT * FROM users WHERE Email_Address = '$Email_Address' AND Password = '$Password'";

$result = mysqli_query($conn, $mysqli_query);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $id = $row["id"]; // ID пользователя
    $Name = $row["Name"];

    // Возвращаем ID и имя пользователя
    echo json_encode(array("status" => "success", "id" => $id, "name" => $Name));
} else {
    echo json_encode(array("status" => "error", "message" => "Login not successful"));
}

?>
