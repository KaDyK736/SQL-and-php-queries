<?php
require "conn.php";

$Name = $_POST["Name"];
$Email_Address = $_POST["Email_Address"];
$Password = $_POST["Password"];
$Company_Name = $_POST["Company_Name"];
$Phone_Number = $_POST["Phone_Number"];
$Passport = $_POST["Passport"];
$OSAGO = $_POST["OSAGO"];
$Bank_card = $_POST["Bank_card"];

//$Name = '8';
//$Email_Address = '8';
//$Password = '8';
//$Company_Name = '8';
//$Phone_Number = '8';
//$Passport = '8';
//$OSAGO = '8';
//$Bank_card = 1;

// Выполняем запрос на вставку данных
$mysqli_query = "INSERT INTO users (Name, Email_Address, Password, Company_Name, Phone_Number, Passport, OSAGO, Bank_card) 
                 VALUES ('$Name', '$Email_Address', '$Password', '$Company_Name', '$Phone_Number', '$Passport', '$OSAGO', '$Bank_card')";

$result = mysqli_query($conn, $mysqli_query);

if ($result) {
    // Получаем ID последней вставленной записи
    $userId = mysqli_insert_id($conn);

    // Возвращаем ID и статус регистрации
    echo json_encode(array("status" => "success", "id" => $userId, "name" => $Name));
} else {
    // Вывод ошибки SQL
    echo json_encode(array("status" => "error", "message" => "Registration not successful", "error" => mysqli_error($conn)));
}
?>
