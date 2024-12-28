<?php
require "conn.php"; // Подключение к базе данных

header('Content-Type: application/json; charset=utf-8'); // Установка заголовка для JSON-ответа

$response = []; // Массив для формирования ответа

// Получаем данные из POST-запроса
$id = $_POST["id"];
$current_user_id = $_POST["current_user_id"];

//$id = '23';
//$current_user_id = '2';

// Проверяем, что данные переданы
if (!isset($id) || !isset($current_user_id)) {
    $response['status'] = 'error';
    $response['message'] = 'Missing required parameters!';
    echo json_encode($response);
    exit;
}

// Начинаем транзакцию
mysqli_begin_transaction($conn);

try {
    // Получение информации о записи
    $reservation_query = "SELECT price, data, time, id_client FROM reservation WHERE id = '$id'";
    $reservation_result = mysqli_query($conn, $reservation_query);

    if (!$reservation_result || mysqli_num_rows($reservation_result) == 0) {
        throw new Exception("Reservation record not found!");
    }

    $reservation_data = mysqli_fetch_assoc($reservation_result);
    $price = (int)$reservation_data['price'];
    $reservation_date = $reservation_data['data'];
    $reservation_time = $reservation_data['time'];
    $reservation_client = (int)$reservation_data['id_client'];

    // Проверка, что запись принадлежит текущему пользователю
    if ($reservation_client !== (int)$current_user_id) {
        throw new Exception("Unauthorized access to cancel the reservation!");
    }

    // Проверка времени до записи (не позднее чем за 3 дня)
    $current_date_time = new DateTime();
    $reservation_date_time = DateTime::createFromFormat('d.m.y H:i:s', "$reservation_date $reservation_time");

    if (!$reservation_date_time) {
    $reservation_date_time = DateTime::createFromFormat('d.m.y H:i', "$reservation_date $reservation_time");
    }
    if (!$reservation_date_time) {
        throw new Exception("Invalid reservation date or time format!");
    }

    $interval = $current_date_time->diff($reservation_date_time);
    if ($interval->days < 3 || $interval->invert === 1) {
        throw new Exception("Reservation cannot be canceled less than 3 days before!");
    }

    // Получение банковской карты текущего пользователя
    $user_query = "SELECT Bank_card FROM users WHERE id = '$current_user_id'";
    $user_result = mysqli_query($conn, $user_query);

    if (!$user_result || mysqli_num_rows($user_result) == 0) {
        throw new Exception("User not found!");
    }

    $user_data = mysqli_fetch_assoc($user_result);
    $bank_card = $user_data['Bank_card'];

    // Возвращение 85% стоимости
    $refund_amount = (int)($price * 0.85);

    // Проверка текущего баланса
    $bank_query = "SELECT money FROM bank WHERE Card_bank = '$bank_card'";
    $bank_result = mysqli_query($conn, $bank_query);

    if (!$bank_result || mysqli_num_rows($bank_result) == 0) {
        throw new Exception("Bank account not found!");
    }

    $bank_data = mysqli_fetch_assoc($bank_result);
    $current_balance = (int)$bank_data['money'];
    $new_balance = $current_balance + $refund_amount;

    // Обновление баланса
    $update_bank_query = "UPDATE bank SET money = '$new_balance' WHERE Card_bank = '$bank_card'";
    $update_bank_result = mysqli_query($conn, $update_bank_query);

    if (!$update_bank_result) {
        throw new Exception("Error updating bank balance: " . mysqli_error($conn));
    }

    // Обновление записи в таблице reservation
    $update_reservation_query = "UPDATE reservation
                                 SET free = '1', 
                                     brand = '',
                                     model = '',
                                     VRC = '',
                                     year = '',
                                     id_client = '3' -- Возвращение записи в свободное состояние
                                 WHERE id = '$id'";
    $update_reservation_result = mysqli_query($conn, $update_reservation_query);

    if (!$update_reservation_result) {
        throw new Exception("Error updating reservation record: " . mysqli_error($conn));
    }

    // Фиксация транзакции
    mysqli_commit($conn);

    $response['status'] = 'success';
    $response['message'] = 'Reservation canceled and refund processed successfully!';
} catch (Exception $e) {
    // Откат транзакции в случае ошибки
    mysqli_rollback($conn);
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

// Возвращаем JSON-ответ
echo json_encode($response);
?>
