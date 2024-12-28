<?php
require "conn.php"; // Подключение к базе данных

header('Content-Type: application/json; charset=utf-8'); // Установка заголовка для JSON-ответа

$response = []; // Массив для формирования ответа

// Логирование входящих данных
file_put_contents("log.txt", "Received POST data: " . json_encode($_POST) . PHP_EOL, FILE_APPEND);

// Получаем данные из POST-запроса
$id = $_POST["id"];
$brand = $_POST["brand"];
$model = $_POST["model"];
$VRC = $_POST["VRC"];
$year = $_POST["year"];
$current_user_id = $_POST["current_user_id"]; // ID текущего пользователя, совершающего действие

//$id = 22;
//$brand ='34';
//$model = '34';
//$VRC = '34';
//$year = '34';
//$current_user_id = 2; // ID текущего пользователя, совершающего действие

// Проверяем, что все данные переданы
if (!isset($id) || !isset($brand) || !isset($model) || !isset($VRC) || !isset($year) || !isset($current_user_id)) {
    $response['status'] = 'error';
    $response['message'] = 'Missing required parameters!';
    file_put_contents("log.txt", "Error: Missing parameters" . PHP_EOL, FILE_APPEND);
    echo json_encode($response);
    exit;
}

// Начинаем транзакцию
mysqli_begin_transaction($conn);

try {
    // Лог начала транзакции
    file_put_contents("log.txt", "Transaction started" . PHP_EOL, FILE_APPEND);

    // Получение стоимости услуги
    $reservation_query = "SELECT price FROM reservation WHERE id = '$id'";
    $reservation_result = mysqli_query($conn, $reservation_query);

    if (!$reservation_result || mysqli_num_rows($reservation_result) == 0) {
        throw new Exception("Reservation record not found!");
    }

    $reservation_data = mysqli_fetch_assoc($reservation_result);
    $price = (int)$reservation_data['price']; // Стоимость услуги

    // Лог успешного получения стоимости
    file_put_contents("log.txt", "Reservation price: $price" . PHP_EOL, FILE_APPEND);

    // Получение банковской карты текущего пользователя
    $user_query = "SELECT Bank_card FROM users WHERE id = '$current_user_id'";
    $user_result = mysqli_query($conn, $user_query);

    if (!$user_result || mysqli_num_rows($user_result) == 0) {
        throw new Exception("User not found!");
    }

    $user_data = mysqli_fetch_assoc($user_result);
    $bank_card = $user_data['Bank_card'];

    // Лог успешного получения банковской карты
    file_put_contents("log.txt", "User bank card: $bank_card" . PHP_EOL, FILE_APPEND);

    // Проверка текущего баланса
    $bank_query = "SELECT money FROM bank WHERE Card_bank = '$bank_card'";
    $bank_result = mysqli_query($conn, $bank_query);

    if (!$bank_result || mysqli_num_rows($bank_result) == 0) {
        throw new Exception("Bank account not found!");
    }

    $bank_data = mysqli_fetch_assoc($bank_result);
    $current_balance = (int)$bank_data['money'];

    // Лог текущего баланса
    file_put_contents("log.txt", "Current balance: $current_balance" . PHP_EOL, FILE_APPEND);

    if ($current_balance < $price) {
        throw new Exception("Insufficient funds!");
    }

    // Обновление баланса
    $new_balance = $current_balance - $price;
    $update_bank_query = "UPDATE bank SET money = '$new_balance' WHERE Card_bank = '$bank_card'";
    $update_bank_result = mysqli_query($conn, $update_bank_query);

    if (!$update_bank_result) {
        throw new Exception("Error updating bank balance: " . mysqli_error($conn));
    }

    // Лог обновления баланса
    file_put_contents("log.txt", "New balance updated: $new_balance" . PHP_EOL, FILE_APPEND);

    // Обновление записи в таблице reservation
    $update_reservation_query = "UPDATE reservation
                                 SET free = '0',
                                     brand = '$brand',
                                     model = '$model',
                                     VRC = '$VRC',
                                     year = '$year',
                                     id_client = '$current_user_id' -- Обновление id_client
                                 WHERE id = '$id'";
    $update_reservation_result = mysqli_query($conn, $update_reservation_query);

    if (!$update_reservation_result) {
        throw new Exception("Error updating reservation record: " . mysqli_error($conn));
    }

    // Лог успешного обновления reservation
    file_put_contents("log.txt", "Reservation updated successfully" . PHP_EOL, FILE_APPEND);

    // Фиксация транзакции
    mysqli_commit($conn);

    $response['status'] = 'success';
    $response['message'] = 'Record updated and payment processed successfully!';
} catch (Exception $e) {
    // Откат транзакции в случае ошибки
    mysqli_rollback($conn);
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    file_put_contents("log.txt", "Transaction rolled back. Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
}

// Лог завершения выполнения
file_put_contents("log.txt", "Response sent: " . json_encode($response) . PHP_EOL, FILE_APPEND);

echo json_encode($response); // Возвращаем JSON-ответ
mail('login@mail.com', 'Все ок!', '');
?>
